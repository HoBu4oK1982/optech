"use client";

import { useEffect, useMemo, useRef } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import "./canvasNotFound.css";

type Dot = {
  x: number;
  y: number;
  vx: number;
  vy: number;
  r: number;
  phase: number;
};

const SUPPORTED_LOCALES = new Set(["ru", "en", "kz"]);

const text = {
  kicker: "\u0411\u0438\u0442\u044b\u0439 \u043c\u0430\u0440\u0448\u0440\u0443\u0442 \u043d\u0435 \u043d\u0430\u0439\u0434\u0435\u043d",
  title: "\u0421\u0442\u0440\u0430\u043d\u0438\u0446\u0430 \u043f\u043e\u0442\u0435\u0440\u044f\u043b\u0430\u0441\u044c \u0432 \u043e\u043f\u0442\u0438\u0447\u0435\u0441\u043a\u043e\u0439 \u0441\u0435\u0442\u0438",
  description: "\u0410\u0434\u0440\u0435\u0441 \u0432\u0435\u0434\u0451\u0442 \u0432 \u043f\u0443\u0441\u0442\u043e\u0439 \u043a\u0430\u043d\u0430\u043b. \u0412\u0435\u0440\u043d\u0451\u043c \u0442\u0435\u0431\u044f \u043d\u0430 \u0433\u043b\u0430\u0432\u043d\u0443\u044e \u0438\u043b\u0438 \u0441\u0440\u0430\u0437\u0443 \u0432 \u043a\u0430\u0442\u0430\u043b\u043e\u0433 OPTECH.",
  home: "\u041d\u0430 \u0433\u043b\u0430\u0432\u043d\u0443\u044e",
  catalog: "\u0412 \u043a\u0430\u0442\u0430\u043b\u043e\u0433",
  channel: "\u041a\u0430\u043d\u0430\u043b",
  signal: "\u0421\u0438\u0433\u043d\u0430\u043b",
  notFound: "\u043d\u0435 \u043d\u0430\u0439\u0434\u0435\u043d",
  solution: "\u0420\u0435\u0448\u0435\u043d\u0438\u0435",
  newRoute: "\u043d\u043e\u0432\u044b\u0439 \u043c\u0430\u0440\u0448\u0440\u0443\u0442",
};

function getLocaleFromPath(pathname: string | null) {
  const first = (pathname || "").split("/").filter(Boolean)[0];
  return SUPPORTED_LOCALES.has(first) ? first : "ru";
}

export default function CanvasNotFound() {
  const canvasRef = useRef<HTMLCanvasElement | null>(null);
  const pathname = usePathname();
  const locale = useMemo(() => getLocaleFromPath(pathname), [pathname]);
  const homeHref = `/${locale}`;
  const catalogHref = `/${locale}/catalog`;

  useEffect(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;

    const ctx = canvas.getContext("2d", { alpha: true });
    if (!ctx) return;

    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    let frame = 0;
    let lastTime = 0;
    let width = 0;
    let height = 0;
    let dpr = 1;
    let dots: Dot[] = [];
    let running = true;

    const random = (min: number, max: number) => min + Math.random() * (max - min);

    const resize = () => {
      const rect = canvas.getBoundingClientRect();
      width = Math.max(320, Math.floor(rect.width));
      height = Math.max(360, Math.floor(rect.height));
      dpr = Math.min(window.devicePixelRatio || 1, 1.5);
      canvas.width = Math.floor(width * dpr);
      canvas.height = Math.floor(height * dpr);
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

      const dotCount = width < 680 ? 24 : 42;
      dots = Array.from({ length: dotCount }, () => ({
        x: random(0, width),
        y: random(0, height),
        vx: random(-0.16, 0.16),
        vy: random(-0.12, 0.12),
        r: random(1.2, 2.7),
        phase: random(0, Math.PI * 2),
      }));
    };

    const drawGrid = (time: number) => {
      ctx.save();
      ctx.globalAlpha = 0.22;
      ctx.strokeStyle = "rgba(83, 180, 255, 0.28)";
      ctx.lineWidth = 1;
      const gap = width < 680 ? 42 : 52;
      const offset = (time * 0.006) % gap;
      for (let x = -gap; x < width + gap; x += gap) {
        ctx.beginPath();
        ctx.moveTo(x + offset, 0);
        ctx.lineTo(x + offset, height);
        ctx.stroke();
      }
      for (let y = -gap; y < height + gap; y += gap) {
        ctx.beginPath();
        ctx.moveTo(0, y + offset * 0.5);
        ctx.lineTo(width, y + offset * 0.5);
        ctx.stroke();
      }
      ctx.restore();
    };

    const drawFiber = (time: number) => {
      const centerY = height * 0.55;
      [0, 1, 2].forEach((_, index) => {
        const alpha = 0.11 + index * 0.05;
        const y = centerY + (index - 1) * 42;
        ctx.save();
        ctx.globalAlpha = alpha;
        ctx.strokeStyle = index === 1 ? "rgba(78, 176, 251, 0.95)" : "rgba(255, 255, 255, 0.72)";
        ctx.lineWidth = index === 1 ? 2 : 1;
        ctx.beginPath();
        ctx.moveTo(-40, y);
        ctx.bezierCurveTo(width * 0.26, y - 150, width * 0.42, y + 140, width * 0.67, y - 28);
        ctx.bezierCurveTo(width * 0.82, y - 128, width * 0.94, y + 72, width + 50, y - 18);
        ctx.stroke();
        ctx.restore();
      });

      const pulseX = ((time * 0.08) % (width + 180)) - 90;
      const pulseY = centerY + Math.sin(time * 0.002) * 40;
      const glow = ctx.createRadialGradient(pulseX, pulseY, 0, pulseX, pulseY, 92);
      glow.addColorStop(0, "rgba(78, 176, 251, 0.55)");
      glow.addColorStop(0.45, "rgba(78, 176, 251, 0.16)");
      glow.addColorStop(1, "rgba(78, 176, 251, 0)");
      ctx.fillStyle = glow;
      ctx.beginPath();
      ctx.arc(pulseX, pulseY, 92, 0, Math.PI * 2);
      ctx.fill();
    };

    const drawDots = (time: number) => {
      ctx.save();
      for (let i = 0; i < dots.length; i += 1) {
        const dot = dots[i];
        if (!reduceMotion) {
          dot.x += dot.vx;
          dot.y += dot.vy;
          if (dot.x < -20) dot.x = width + 20;
          if (dot.x > width + 20) dot.x = -20;
          if (dot.y < -20) dot.y = height + 20;
          if (dot.y > height + 20) dot.y = -20;
        }

        const wave = 0.5 + Math.sin(time * 0.002 + dot.phase) * 0.5;
        ctx.globalAlpha = 0.24 + wave * 0.36;
        ctx.fillStyle = i % 6 === 0 ? "#ff9f1c" : "#4eb0fb";
        ctx.beginPath();
        ctx.arc(dot.x, dot.y, dot.r + wave * 0.9, 0, Math.PI * 2);
        ctx.fill();
      }

      ctx.globalAlpha = 0.18;
      ctx.strokeStyle = "rgba(78, 176, 251, 0.72)";
      ctx.lineWidth = 1;
      for (let i = 0; i < dots.length; i += 1) {
        for (let j = i + 1; j < dots.length; j += 1) {
          const a = dots[i];
          const b = dots[j];
          const dx = a.x - b.x;
          const dy = a.y - b.y;
          const dist = Math.sqrt(dx * dx + dy * dy);
          if (dist < 116) {
            ctx.globalAlpha = (1 - dist / 116) * 0.24;
            ctx.beginPath();
            ctx.moveTo(a.x, a.y);
            ctx.lineTo(b.x, b.y);
            ctx.stroke();
          }
        }
      }
      ctx.restore();
    };

    const draw = (time: number) => {
      const bg = ctx.createLinearGradient(0, 0, width, height);
      bg.addColorStop(0, "#061b37");
      bg.addColorStop(0.5, "#073b77");
      bg.addColorStop(1, "#0c5fbd");
      ctx.fillStyle = bg;
      ctx.fillRect(0, 0, width, height);

      drawGrid(time);
      drawFiber(time);
      drawDots(time);

      const scanY = ((time * 0.045) % (height + 120)) - 60;
      const scan = ctx.createLinearGradient(0, scanY - 38, 0, scanY + 38);
      scan.addColorStop(0, "rgba(78,176,251,0)");
      scan.addColorStop(0.5, "rgba(78,176,251,0.18)");
      scan.addColorStop(1, "rgba(78,176,251,0)");
      ctx.fillStyle = scan;
      ctx.fillRect(0, scanY - 38, width, 76);
    };

    const tick = (time: number) => {
      if (!running) return;
      frame = window.requestAnimationFrame(tick);
      if (time - lastTime < 33) return;
      lastTime = time;
      draw(time);
      if (reduceMotion) {
        running = false;
        window.cancelAnimationFrame(frame);
      }
    };

    const onVisibility = () => {
      running = !document.hidden;
      if (running) {
        lastTime = 0;
        frame = window.requestAnimationFrame(tick);
      }
    };

    resize();
    draw(0);
    if (!reduceMotion) frame = window.requestAnimationFrame(tick);
    window.addEventListener("resize", resize, { passive: true });
    document.addEventListener("visibilitychange", onVisibility);

    return () => {
      running = false;
      window.cancelAnimationFrame(frame);
      window.removeEventListener("resize", resize);
      document.removeEventListener("visibilitychange", onVisibility);
    };
  }, []);

  return (
    <main className="op404">
      <canvas ref={canvasRef} className="op404-canvas" aria-hidden="true" />
      <div className="op404-vignette" aria-hidden="true" />

      <section className="op404-inner">
        <div className="op404-copy">
          <div className="op404-kicker">
            <span />
            {text.kicker}
          </div>
          <div className="op404-code" aria-label="404">
            <span>4</span>
            <span>0</span>
            <span>4</span>
          </div>
          <h1>{text.title}</h1>
          <p>{text.description}</p>
          <div className="op404-actions">
            <Link href={homeHref} className="op404-primary">{text.home}</Link>
            <Link href={catalogHref} className="op404-secondary">{text.catalog}</Link>
          </div>
        </div>

        <aside className="op404-terminal" aria-label="OPTECH diagnostics">
          <div className="op404-terminalTop">
            <i />
            <i />
            <i />
          </div>
          <div className="op404-terminalTitle">OPTECH ROUTE CHECK</div>
          <div className="op404-status op404-statusBad">
            <span>{text.channel}</span>
            <b>404</b>
          </div>
          <div className="op404-status">
            <span>{text.signal}</span>
            <b>{text.notFound}</b>
          </div>
          <div className="op404-status">
            <span>{text.solution}</span>
            <b>{text.newRoute}</b>
          </div>
          <div className="op404-bars" aria-hidden="true">
            <span />
            <span />
            <span />
            <span />
            <span />
          </div>
        </aside>
      </section>
    </main>
  );
}
