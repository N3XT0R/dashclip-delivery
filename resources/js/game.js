
    const canvas = document.getElementById('gameCanvas');
    const ctx = canvas.getContext('2d');

    // === KONFIGURATION ===
    const INITIAL_PLAYER_SPEED = 500;   // Spieler-Startgeschwindigkeit in px/s
    const SPEED_INCREMENT = 20;         // Zuwachs pro Clip
    const SPEED_CAP = 900;               // Max. Geschwindigkeit Spieler
    const INITIAL_ENEMY_SPEED = 200;    // Gegner-Startgeschwindigkeit in px/s

    // HUD
    const scoreEl = document.getElementById('score');
    const timeEl = document.getElementById('time');
    const bestEl = document.getElementById('best');
    const restartBtn = document.getElementById('restart');

    // Persist best score
    const BEST_KEY = 'dashclip_game_best';
    let best = 0;
    try { best = Number(localStorage.getItem(BEST_KEY) || 0); } catch {}
    bestEl.textContent = best;

    // Game state
    const W = canvas.width, H = canvas.height;
    const player = {x: W / 2, y: H / 2, r: 12, speed: INITIAL_PLAYER_SPEED};
    let inputs = {up: false, down: false, left: false, right: false};
    let clips = [];
    let enemy = {x: 80, y: 70, r: 12, dx: INITIAL_ENEMY_SPEED, dy: INITIAL_ENEMY_SPEED};
    let score = 0;
    let timeLeft = 90; // seconds
    let running = true;
    let lastTime = 0;
    let spawnTimer = 0;

    function clamp(v, min, max) {
        return Math.max(min, Math.min(max, v));
    }

    function dist(ax, ay, bx, by) {
        const dx = ax - bx, dy = ay - by;
        return Math.hypot(dx, dy);
    }

    function rand(min, max) {
        return Math.random() * (max - min) + min;
    }

    function spawnClip() {
        const r = 8;
        const x = rand(r + 4, W - r - 4);
        const y = rand(r + 4, H - r - 4);
        clips.push({x, y, r});
    }

    function reset() {
        player.x = W / 2;
        player.y = H / 2;
        player.speed = INITIAL_PLAYER_SPEED;
        enemy = {
            x: rand(40, W - 40),
            y: rand(40, H - 40),
            r: 12,
            dx: INITIAL_ENEMY_SPEED * (Math.random() > 0.5 ? 1 : -1),
            dy: INITIAL_ENEMY_SPEED * (Math.random() > 0.5 ? 1 : -1)
        };
        clips = [];
        for (let i = 0; i < 6; i++) spawnClip();
        score = 0;
        scoreEl.textContent = score;
        timeLeft = 90;
        timeEl.textContent = timeLeft;
        running = true;
        lastTime = performance.now();
        spawnTimer = 0;
    }

    // Input
    const keyMap = {
        'ArrowUp': 'up', 'KeyW': 'up',
        'ArrowDown': 'down', 'KeyS': 'down',
        'ArrowLeft': 'left', 'KeyA': 'left',
        'ArrowRight': 'right', 'KeyD': 'right'
    };
    addEventListener('keydown', e => {
        const k = keyMap[e.code];
        if (k && document.activeElement === canvas) {
            inputs[k] = true;
            e.preventDefault();
        }
    });
    addEventListener('keyup', e => {
        const k = keyMap[e.code];
        if (k && document.activeElement === canvas) {
            inputs[k] = false;
            e.preventDefault();
        }
    });
    restartBtn.addEventListener('click', () => { reset(); canvas.focus(); });
    canvas.addEventListener('blur', () => { Object.keys(inputs).forEach(key => { inputs[key] = false; }); });

    function update(dt) {
        if (!running) return;

        updateClockAndSpawn(dt);
        movePlayer(dt);
        moveEnemy(dt);
        collectClips();
        if (dist(player.x, player.y, enemy.x, enemy.y) < player.r + enemy.r) {
            running = false;
            endGame();
        }
    }

    function updateClockAndSpawn(dt) {
        spawnTimer += dt;
        if (spawnTimer > 0.7) {
            spawnClip();
            spawnTimer = 0;
        }
        timeLeft -= dt;
        if (timeLeft <= 0) {
            timeLeft = 0;
            running = false;
            endGame();
        }
        timeEl.textContent = Math.ceil(timeLeft);

    }

    function movePlayer(dt) {
        let vx = 0, vy = 0;
        if (inputs.up) vy -= 1;
        if (inputs.down) vy += 1;
        if (inputs.left) vx -= 1;
        if (inputs.right) vx += 1;
        if (vx || vy) {
            const len = Math.hypot(vx, vy);
            vx /= len;
            vy /= len;
        }
        player.x = clamp(player.x + vx * player.speed * dt, player.r, W - player.r);
        player.y = clamp(player.y + vy * player.speed * dt, player.r, H - player.r);

    }

    function moveEnemy(dt) {
        enemy.x += enemy.dx * dt;
        enemy.y += enemy.dy * dt;
        if (enemy.x - enemy.r < 0 || enemy.x + enemy.r > W) enemy.dx *= -1;
        if (enemy.y - enemy.r < 0 || enemy.y + enemy.r > H) enemy.dy *= -1;

    }

    function collectClips() {
        for (let i = clips.length - 1; i >= 0; i--) {
            const c = clips[i];
            if (dist(player.x, player.y, c.x, c.y) < player.r + c.r) {
                clips.splice(i, 1);
                score++;
                scoreEl.textContent = score;
                player.speed = Math.min(player.speed + SPEED_INCREMENT, SPEED_CAP);
            }
        }
    }

    function endGame() {
        best = Math.max(best, score);
        try { localStorage.setItem(BEST_KEY, String(best)); } catch {}
        bestEl.textContent = best;
    }

    function draw() {
        ctx.clearRect(0, 0, W, H);
        // grid background
        ctx.save();
        ctx.strokeStyle = '#12203a';
        ctx.lineWidth = 1;
        ctx.globalAlpha = 0.6;
        for (let i = 0; i < W; i += 20) {
            ctx.beginPath();
            ctx.moveTo(i, 0);
            ctx.lineTo(i, H);
            ctx.stroke();
        }
        for (let j = 0; j < H; j += 20) {
            ctx.beginPath();
            ctx.moveTo(0, j);
            ctx.lineTo(W, j);
            ctx.stroke();
        }
        ctx.restore();

        // clips
        for (const c of clips) {
            ctx.beginPath();
            ctx.arc(c.x, c.y, c.r, 0, Math.PI * 2);
            ctx.fillStyle = '#22c55e';
            ctx.fill();
        }
        // enemy
        ctx.beginPath();
        ctx.arc(enemy.x, enemy.y, enemy.r, 0, Math.PI * 2);
        ctx.fillStyle = '#ef4444';
        ctx.fill();
        // player
        ctx.beginPath();
        ctx.arc(player.x, player.y, player.r, 0, Math.PI * 2);
        ctx.fillStyle = '#4f46e5';
        ctx.fill();

        if (!running) {
            ctx.fillStyle = 'rgba(0,0,0,.55)';
            ctx.fillRect(0, 0, W, H);
            ctx.fillStyle = '#e5e7eb';
            ctx.font = 'bold 28px Inter, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('Game Over', W / 2, H / 2 - 8);
            ctx.font = '14px Inter, sans-serif';
            ctx.fillText(`Score: ${score} · Best: ${best}  —  Drücke "Neu starten"`, W / 2, H / 2 + 18);
        }
    }

    function loop(ts) {
        const dt = (ts - lastTime) / 1000 || 0;
        lastTime = ts;
        update(dt);
        draw();
        requestAnimationFrame(loop);
    }

    reset();
    requestAnimationFrame(loop);
