#!/usr/bin/env python3
"""Blur number plates in a video.

Reads the video through ffmpeg, looks at every n-th frame in tiles so small plates survive the
scaling, keeps the found regions for the frames in between, widening them as they age because
what they cover keeps moving, and writes the blurred video back through ffmpeg. Only the lower part of the picture is searched, because number plates are not in
the sky. Runs on the processor, no graphics card needed.
"""
from __future__ import annotations

import argparse
import json
import subprocess
import sys

import numpy as np
import onnxruntime as ort

MODEL_EDGE = 640


def arguments() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--input', required=True)
    parser.add_argument('--output', required=True)
    parser.add_argument('--model', required=True)
    parser.add_argument('--columns', type=int, default=3)
    parser.add_argument('--rows', type=int, default=2)
    parser.add_argument('--frame-step', type=int, default=3)
    parser.add_argument('--confidence', type=float, default=0.15)
    parser.add_argument('--margin', type=float, default=0.25)
    parser.add_argument('--search-from', type=float, default=0.0)
    parser.add_argument('--margin-growth', type=float, default=0.2)
    parser.add_argument('--threads', type=int, default=2)
    return parser.parse_args()


def probe(path: str) -> tuple[int, int, str]:
    out = subprocess.run(
        ['ffprobe', '-v', 'error', '-select_streams', 'v:0',
         '-show_entries', 'stream=width,height,r_frame_rate',
         '-of', 'default=noprint_wrappers=1:nokey=1', path],
        capture_output=True, text=True, check=True,
    ).stdout.split()
    return int(out[0]), int(out[1]), out[2]


def resize(image: np.ndarray, edge: int) -> np.ndarray:
    height, width = image.shape[:2]
    rows = (np.arange(edge) * height / edge).astype(np.int32)
    columns = (np.arange(edge) * width / edge).astype(np.int32)
    return image[rows][:, columns]


def detect(session, name: str, image: np.ndarray, confidence: float) -> list[tuple[int, int, int, int]]:
    height, width = image.shape[:2]
    tensor = (resize(image, MODEL_EDGE).astype(np.float32) / 255.0).transpose(2, 0, 1)[None]
    prediction = session.run(None, {name: tensor})[0][0]
    scores = prediction[4:].max(axis=0)
    keep = scores > confidence
    boxes = []
    for cx, cy, box_width, box_height in prediction[:4, keep].T:
        x1 = (cx - box_width / 2) * width / MODEL_EDGE
        y1 = (cy - box_height / 2) * height / MODEL_EDGE
        x2 = (cx + box_width / 2) * width / MODEL_EDGE
        y2 = (cy + box_height / 2) * height / MODEL_EDGE
        boxes.append((int(x1), int(y1), int(x2), int(y2)))
    return boxes


def detect_tiled(session, name, frame, args) -> list[tuple[int, int, int, int]]:
    """Search the band below ``--search-from`` only, laid out as the configured tiles."""
    height, width = frame.shape[:2]
    top = min(max(int(height * args.search_from), 0), height - 1)
    band = height - top
    found = []
    for row in range(args.rows):
        for column in range(args.columns):
            x0 = int(column * width / args.columns)
            y0 = top + int(row * band / args.rows)
            x1 = int((column + 1) * width / args.columns)
            y1 = top + int((row + 1) * band / args.rows)
            for (bx1, by1, bx2, by2) in detect(session, name, frame[y0:y1, x0:x1], args.confidence):
                found.append((bx1 + x0, by1 + y0, bx2 + x0, by2 + y0))
    return found


def widen(box, margin: float, width: int, height: int):
    x1, y1, x2, y2 = box
    pad_x = int((x2 - x1) * margin) + 4
    pad_y = int((y2 - y1) * margin) + 4
    return (
        max(x1 - pad_x, 0), max(y1 - pad_y, 0),
        min(x2 + pad_x, width), min(y2 + pad_y, height),
    )


def blur(frame: np.ndarray, boxes) -> None:
    """Replace every region with a coarse mosaic, which no sharpening brings back."""
    for (x1, y1, x2, y2) in boxes:
        region = frame[y1:y2, x1:x2]
        if region.size == 0:
            continue
        tile = max((x2 - x1) // 6, (y2 - y1) // 6, 4)
        for top in range(0, region.shape[0], tile):
            for left in range(0, region.shape[1], tile):
                patch = region[top:top + tile, left:left + tile]
                if patch.size:
                    patch[:] = patch.mean(axis=(0, 1)).astype(np.uint8)


def main() -> int:
    args = arguments()
    width, height, rate = probe(args.input)

    options = ort.SessionOptions()
    options.intra_op_num_threads = args.threads
    options.inter_op_num_threads = 1
    session = ort.InferenceSession(args.model, options, providers=['CPUExecutionProvider'])
    name = session.get_inputs()[0].name

    reader = subprocess.Popen(
        ['ffmpeg', '-loglevel', 'error', '-i', args.input,
         '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-'],
        stdout=subprocess.PIPE,
    )
    writer = subprocess.Popen(
        ['ffmpeg', '-y', '-loglevel', 'error',
         '-f', 'rawvideo', '-pix_fmt', 'rgb24', '-s', f'{width}x{height}', '-r', rate, '-i', '-',
         '-i', args.input, '-map', '0:v:0', '-map', '1:a?', '-c:a', 'copy',
         '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '23', '-pix_fmt', 'yuv420p',
         args.output],
        stdin=subprocess.PIPE,
    )

    frame_bytes = width * height * 3
    index = 0
    looked_at = 0
    regions = 0
    found: list[tuple[int, int, int, int]] = []

    while True:
        raw = reader.stdout.read(frame_bytes)
        if len(raw) < frame_bytes:
            break
        frame = np.frombuffer(raw, np.uint8).reshape(height, width, 3).copy()

        age = index % args.frame_step
        if age == 0:
            found = detect_tiled(session, name, frame, args)
            looked_at += 1
            regions += len(found)

        # a region found a few frames ago has moved on since, so its cover grows with its age
        margin = args.margin + args.margin_growth * age
        boxes = [widen(box, margin, width, height) for box in found]

        blur(frame, boxes)
        writer.stdin.write(frame.tobytes())
        index += 1

    reader.stdout.close()
    writer.stdin.close()
    writer.wait()
    reader.wait()

    print(json.dumps({'frames': looked_at, 'regions': regions, 'total_frames': index}))
    return 0 if writer.returncode == 0 else 1


if __name__ == '__main__':
    sys.exit(main())
