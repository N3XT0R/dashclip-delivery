#!/usr/bin/env node
// Build the cover artwork for the weekly blog articles and the release news.
//
//   node database/seeders/data/images/build-covers.mjs [key ...]
//
// Reads the article list from blog-editorial-queue.php and the release news from
// releases/<version>.php, writes one SVG per article to src/ and rasterises each one to
// covers/<key>.webp at 1280x720, the aspect ratio the blog cards and the social preview both
// expect. Pass article keys to render only those covers and leave the committed ones untouched.
// Both the sources and the rendered files are committed, so seeding never depends on this
// script being run again.

import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const sourceDirectory = join(here, 'src');
const targetDirectory = join(here, 'covers');

const WIDTH = 1280;
const HEIGHT = 720;
const INK = '#0c1924';
const PANEL = '#132431';
const BRAND = '#f97316';
const TEXT = '#edf2f6';
const MUTED = '#b5c2ce';

const CATEGORY_LABEL = {
    'news': 'Neuigkeiten',
    'dashcam-knowledge': 'Dashcam-Wissen',
    'submission-tips': 'Tipps zum Einsenden',
};

/** Stable pseudo random number per article key, so a cover never changes between runs. */
const seeded = (key) => {
    let hash = 2166136261;
    for (const character of key) {
        hash ^= character.charCodeAt(0);
        hash = Math.imul(hash, 16777619);
    }
    return () => {
        hash = Math.imul(hash ^ (hash >>> 15), 2246822507);
        hash = Math.imul(hash ^ (hash >>> 13), 3266489909);
        return ((hash ^= hash >>> 16) >>> 0) / 4294967296;
    };
};

const escape = (value) => value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

/** Wrap a headline into lines that fit the artwork, estimating the width of DejaVu Sans Bold. */
const wrap = (title, fontSize, maxWidth) => {
    const widthOf = (text) => text.length * fontSize * 0.55;
    const lines = [];
    let line = '';
    for (const word of title.split(' ')) {
        const candidate = line === '' ? word : `${line} ${word}`;
        if (widthOf(candidate) > maxWidth && line !== '') {
            lines.push(line);
            line = word;
        } else {
            line = candidate;
        }
    }
    if (line !== '') {
        lines.push(line);
    }
    return lines;
};

/** Draw the motif that belongs to a category, placed by the article specific random source. */
const motif = (category, random) => {
    const drift = (range) => Math.round((random() - 0.5) * range);
    if (category === 'dashcam-knowledge') {
        const cx = 1010 + drift(60);
        const cy = 250 + drift(50);
        return `
    <circle cx="${cx}" cy="${cy}" r="150" fill="none" stroke="${BRAND}" stroke-opacity="0.45" stroke-width="3"/>
    <circle cx="${cx}" cy="${cy}" r="104" fill="none" stroke="${BRAND}" stroke-opacity="0.7" stroke-width="5"/>
    <circle cx="${cx}" cy="${cy}" r="46" fill="${BRAND}" fill-opacity="0.22"/>
    <circle cx="${cx}" cy="${cy}" r="16" fill="${BRAND}"/>
    <path d="M${cx - 240} ${cy + 210} L${cx + 240} ${cy + 210}" stroke="${MUTED}" stroke-opacity="0.25" stroke-width="6" stroke-dasharray="34 26"/>`;
    }
    if (category === 'submission-tips') {
        const x = 900 + drift(50);
        const y = 200 + drift(40);
        return `
    <rect x="${x}" y="${y}" width="230" height="140" rx="18" fill="${BRAND}" fill-opacity="0.16" stroke="${BRAND}" stroke-opacity="0.55" stroke-width="3"/>
    <rect x="${x + 34}" y="${y + 74}" width="230" height="140" rx="18" fill="${BRAND}" fill-opacity="0.22" stroke="${BRAND}" stroke-opacity="0.7" stroke-width="3"/>
    <path d="M${x + 149} ${y + 196} L${x + 149} ${y + 118} M${x + 113} ${y + 154} L${x + 149} ${y + 118} L${x + 185} ${y + 154}" fill="none" stroke="${BRAND}" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>`;
    }
    const x = 930 + drift(50);
    const y = 230 + drift(40);
    return `
    <circle cx="${x}" cy="${y}" r="22" fill="${BRAND}"/>
    <path d="M${x + 54} ${y - 54} a76 76 0 0 1 0 108" fill="none" stroke="${BRAND}" stroke-opacity="0.75" stroke-width="7" stroke-linecap="round"/>
    <path d="M${x + 104} ${y - 104} a147 147 0 0 1 0 208" fill="none" stroke="${BRAND}" stroke-opacity="0.5" stroke-width="7" stroke-linecap="round"/>
    <path d="M${x - 54} ${y + 54} a76 76 0 0 1 0 -108" fill="none" stroke="${BRAND}" stroke-opacity="0.75" stroke-width="7" stroke-linecap="round"/>
    <path d="M${x - 104} ${y + 104} a147 147 0 0 1 0 -208" fill="none" stroke="${BRAND}" stroke-opacity="0.5" stroke-width="7" stroke-linecap="round"/>`;
};

const cover = (key, category, title) => {
    const random = seeded(key);
    const fontSize = title.length > 58 ? 52 : 60;
    const lines = wrap(title, fontSize, 760);
    const firstBaseline = 470 - (lines.length - 1) * (fontSize + 14);
    const headline = lines
        .map((line, index) => `<tspan x="96" y="${firstBaseline + index * (fontSize + 14)}">${escape(line)}</tspan>`)
        .join('');

    return `<svg xmlns="http://www.w3.org/2000/svg" width="${WIDTH}" height="${HEIGHT}" viewBox="0 0 ${WIDTH} ${HEIGHT}" role="img">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="${INK}"/>
      <stop offset="1" stop-color="${PANEL}"/>
    </linearGradient>
  </defs>
  <rect width="${WIDTH}" height="${HEIGHT}" fill="url(#bg)"/>
  <path d="M0 ${HEIGHT} L${WIDTH} ${HEIGHT} L${WIDTH} ${HEIGHT - 180} Z" fill="${BRAND}" fill-opacity="0.08"/>
  ${motif(category, random)}
  <rect x="96" y="96" width="76" height="6" rx="3" fill="${BRAND}"/>
  <text x="96" y="152" font-family="DejaVu Sans" font-size="24" font-weight="bold" letter-spacing="3" fill="${BRAND}">${escape(CATEGORY_LABEL[category].toUpperCase())}</text>
  <text font-family="DejaVu Sans" font-size="${fontSize}" font-weight="bold" fill="${TEXT}">${headline}</text>
  <text x="96" y="604" font-family="DejaVu Sans" font-size="26" fill="${MUTED}">DashClip Delivery</text>
</svg>
`;
};

const articles = JSON.parse(
    execFileSync('php', [
        '-r',
        '$d = require "'
            + join(here, '..', 'blog-editorial-queue.php')
            + '"; $out = []; foreach ($d["articles"] as $k => $a) { $out[] = ["key" => $k, "category" => $a["category"], "title" => $a["translations"]["de"]["title"]]; }'
            + ' foreach (glob("'
            + join(here, '..', 'releases')
            + '/*.php") as $f) { $r = require $f; $out[] = ["key" => $r["key"], "category" => "news", "title" => $r["translations"]["de"]["title"]]; }'
            + ' echo json_encode($out);',
    ]).toString(),
).filter((article) => process.argv.length <= 2 || process.argv.slice(2).includes(article.key));

mkdirSync(sourceDirectory, { recursive: true });
mkdirSync(targetDirectory, { recursive: true });

for (const article of articles) {
    writeFileSync(join(sourceDirectory, `${article.key}.svg`), cover(article.key, article.category, article.title));
}

execFileSync(
    'npx',
    [
        '-y', 'sharp-cli',
        '--input', ...articles.map((article) => join(sourceDirectory, `${article.key}.svg`)),
        '--output', targetDirectory,
        '--format', 'webp',
    ],
    { stdio: 'ignore' },
);

process.stdout.write(`${articles.length} covers written to ${targetDirectory}\n`);
