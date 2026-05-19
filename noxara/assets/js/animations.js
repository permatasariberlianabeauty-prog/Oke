/**
 * NOXARA - Visual Animations JS
 * Particle backgrounds, banners, count-up, confetti, etc.
 */

'use strict';

/* ============================================================
   PARTICLE BACKGROUND (canvas)
   ============================================================ */
class ParticleBackground {
  constructor(canvasId = 'particle-canvas') {
    this.canvas  = document.getElementById(canvasId);
    if (!this.canvas) return;
    this.ctx     = this.canvas.getContext('2d');
    this.particles = [];
    this.animId  = null;
    this.resize();
    this.init();
    window.addEventListener('resize', () => this.resize(), { passive: true });
    this.animate();
  }

  resize() {
    if (!this.canvas) return;
    this.canvas.width  = window.innerWidth;
    this.canvas.height = window.innerHeight;
  }

  init() {
    const count = Math.min(60, Math.floor((this.canvas.width * this.canvas.height) / 14000));
    this.particles = [];
    for (let i = 0; i < count; i++) this.particles.push(this.createParticle());
  }

  createParticle() {
    return {
      x:    Math.random() * this.canvas.width,
      y:    Math.random() * this.canvas.height,
      r:    Math.random() * 1.8 + 0.4,
      vx:   (Math.random() - 0.5) * 0.4,
      vy:   -Math.random() * 0.5 - 0.1,
      alpha: Math.random() * 0.4 + 0.1,
      hue:  Math.random() < 0.6 ? 190 : 270, // cyan or purple
    };
  }

  animate() {
    const ctx = this.ctx;
    ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

    this.particles.forEach((p, i) => {
      p.x  += p.vx;
      p.y  += p.vy;
      p.alpha -= 0.0008;

      if (p.alpha <= 0 || p.y < -10 || p.x < -10 || p.x > this.canvas.width + 10) {
        this.particles[i] = this.createParticle();
        this.particles[i].y = this.canvas.height + 5;
        return;
      }

      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = `hsla(${p.hue}, 100%, 65%, ${p.alpha})`;
      ctx.fill();
    });

    // Draw connecting lines between close particles
    for (let i = 0; i < this.particles.length; i++) {
      for (let j = i + 1; j < this.particles.length; j++) {
        const dx   = this.particles[i].x - this.particles[j].x;
        const dy   = this.particles[i].y - this.particles[j].y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < 100) {
          ctx.beginPath();
          ctx.moveTo(this.particles[i].x, this.particles[i].y);
          ctx.lineTo(this.particles[j].x, this.particles[j].y);
          ctx.strokeStyle = `rgba(0,212,255,${0.06 * (1 - dist / 100)})`;
          ctx.lineWidth   = 0.5;
          ctx.stroke();
        }
      }
    }

    this.animId = requestAnimationFrame(() => this.animate());
  }

  destroy() {
    if (this.animId) cancelAnimationFrame(this.animId);
    window.removeEventListener('resize', this.resize);
  }
}

/* ============================================================
   MINING ROBOT FLOATING ANIMATION
   ============================================================ */
function initMiningRobot() {
  document.querySelectorAll('.mining-robot-wrap img, .mining-robot-wrap svg, .gift-box-anim').forEach(el => {
    el.style.animation = 'float 3s ease-in-out infinite';
  });
}

/* ============================================================
   BANNER SLIDER
   ============================================================ */
class BannerSlider {
  constructor(containerEl) {
    this.container = containerEl;
    this.track     = containerEl.querySelector('.banner-track');
    this.slides    = containerEl.querySelectorAll('.banner-slide');
    this.dots      = containerEl.querySelectorAll('.banner-dot');
    this.current   = 0;
    this.total     = this.slides.length;
    this.autoTimer = null;
    if (this.total < 2) return;
    this.start();
    this.bindDots();
    this.bindSwipe();
  }

  goTo(idx) {
    this.current = (idx + this.total) % this.total;
    this.track.style.transform = `translateX(-${this.current * 100}%)`;
    this.dots.forEach((d, i) => d.classList.toggle('active', i === this.current));
  }

  start() {
    this.autoTimer = setInterval(() => this.goTo(this.current + 1), 4000);
    this.goTo(0);
  }

  bindDots() {
    this.dots.forEach((dot, i) => dot.addEventListener('click', () => {
      clearInterval(this.autoTimer);
      this.goTo(i);
      this.start();
    }));
  }

  bindSwipe() {
    let startX = 0;
    this.container.addEventListener('touchstart', (e) => { startX = e.touches[0].clientX; }, { passive: true });
    this.container.addEventListener('touchend', (e) => {
      const dx = e.changedTouches[0].clientX - startX;
      if (Math.abs(dx) > 40) {
        clearInterval(this.autoTimer);
        this.goTo(dx < 0 ? this.current + 1 : this.current - 1);
        this.start();
      }
    }, { passive: true });
  }
}


/* ============================================================
   COUNT-UP NUMBER ANIMATION
   ============================================================ */
function countUp(el, target, duration = 1800, prefix = '', suffix = '') {
  if (!el) return;
  const startTime = performance.now();

  function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }

  function update(now) {
    const elapsed  = now - startTime;
    const progress = Math.min(elapsed / duration, 1);
    const value    = Math.floor(easeOutCubic(progress) * target);
    el.textContent = prefix + (window.formatNumber ? formatNumber(value) : value.toLocaleString('id-ID')) + suffix;
    el.classList.add('count-up-num');
    if (progress < 1) requestAnimationFrame(update);
    else el.textContent = prefix + (window.formatNumber ? formatNumber(target) : target.toLocaleString('id-ID')) + suffix;
  }

  requestAnimationFrame(update);
}

window.countUp = countUp;

/* ============================================================
   SCROLL REVEAL
   ============================================================ */
function initScrollReveal() {
  const els = document.querySelectorAll('.reveal');
  if (!els.length) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  els.forEach(el => observer.observe(el));
}

/* ============================================================
   COIN BURST ANIMATION
   ============================================================ */
function triggerCoinBurst(container, count = 12) {
  if (!container) return;
  for (let i = 0; i < count; i++) {
    const coin = document.createElement('div');
    coin.className = 'coin-particle';
    const angle = (360 / count * i) * (Math.PI / 180);
    const dist  = 50 + Math.random() * 40;
    coin.style.cssText = `
      left: 50%; top: 50%;
      --tx: ${Math.cos(angle) * dist}px;
      --ty: ${Math.sin(angle) * dist - 30}px;
      animation: coin-fall ${0.6 + Math.random() * 0.4}s ease-out ${i * 0.04}s forwards;
    `;
    container.appendChild(coin);
    coin.addEventListener('animationend', () => coin.remove(), { once: true });
  }
}

window.triggerCoinBurst = triggerCoinBurst;

/* ============================================================
   CONFETTI SYSTEM
   ============================================================ */
function triggerConfetti(duration = 3000) {
  let container = document.getElementById('confetti-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'confetti-container';
    container.className = 'confetti-container';
    document.body.appendChild(container);
  }

  const colors = ['#00D4FF', '#7B2FFF', '#00E676', '#FFD700', '#FF3B3B', '#FF9500'];
  const shapes = ['square', 'circle', 'rect'];
  const count  = 80;

  for (let i = 0; i < count; i++) {
    const piece = document.createElement('div');
    piece.className = 'confetti-piece';
    const color = colors[Math.floor(Math.random() * colors.length)];
    const shape = shapes[Math.floor(Math.random() * shapes.length)];
    const size  = 6 + Math.random() * 8;
    piece.style.cssText = `
      left: ${Math.random() * 100}vw;
      width: ${size}px;
      height: ${shape === 'rect' ? size * 2 : size}px;
      background: ${color};
      border-radius: ${shape === 'circle' ? '50%' : shape === 'rect' ? '2px' : '1px'};
      animation: confetti-fall ${1.5 + Math.random() * 2}s linear ${Math.random() * 0.8}s forwards;
      opacity: 0.9;
    `;
    container.appendChild(piece);
    piece.addEventListener('animationend', () => piece.remove(), { once: true });
  }

  setTimeout(() => { if (container.children.length === 0) container.remove(); }, duration + 1000);
}

window.triggerConfetti = triggerConfetti;

/* ============================================================
   PROGRESS BAR ANIMATED FILL
   ============================================================ */
function animateAllProgressBars() {
  document.querySelectorAll('.progress-fill[data-percent]').forEach(bar => {
    const pct = parseFloat(bar.dataset.percent) || 0;
    bar.style.width = '0%';
    setTimeout(() => {
      bar.style.transition = 'width 1s cubic-bezier(0.4,0,0.2,1)';
      bar.style.width = Math.min(100, pct) + '%';
    }, 100);
  });
}

/* ============================================================
   SKELETON → CONTENT TRANSITION
   ============================================================ */
function revealContent(skeletonEl, contentEl, delay = 200) {
  setTimeout(() => {
    skeletonEl.style.transition = 'opacity 0.3s ease';
    skeletonEl.style.opacity    = '0';
    setTimeout(() => {
      skeletonEl.style.display = 'none';
      contentEl.style.opacity  = '0';
      contentEl.style.display  = '';
      requestAnimationFrame(() => {
        contentEl.style.transition = 'opacity 0.3s ease';
        contentEl.style.opacity    = '1';
      });
    }, 300);
  }, delay);
}

window.revealContent = revealContent;

/* ============================================================
   INIT ON DOM READY
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
  // Particle background on landing/auth pages
  const canvas = document.getElementById('particle-canvas');
  if (canvas) { window._particles = new ParticleBackground('particle-canvas'); }

  // Mining robot
  initMiningRobot();

  // Banner sliders
  document.querySelectorAll('.banner-slider').forEach(el => new BannerSlider(el));

  // Scroll reveal
  initScrollReveal();

  // Progress bars
  animateAllProgressBars();

  // Count-up for stat counters
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const el     = entry.target;
        const target = parseFloat(el.dataset.countUp) || 0;
        const prefix = el.dataset.prefix || '';
        const suffix = el.dataset.suffix || '';
        countUp(el, target, 1800, prefix, suffix);
        observer.unobserve(el);
      }
    });
  }, { threshold: 0.3 });

  document.querySelectorAll('[data-count-up]').forEach(el => observer.observe(el));
});
