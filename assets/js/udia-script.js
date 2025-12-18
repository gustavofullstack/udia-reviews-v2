(function () {
  'use strict';

  // ========== UTILITÁRIOS ==========

  /**
   * Verifica se localStorage está disponível
   */
  function isLocalStorageAvailable() {
    try {
      const test = '__test__';
      localStorage.setItem(test, test);
      localStorage.removeItem(test);
      return true;
    } catch (e) {
      return false;
    }
  }

  const hasLocalStorage = isLocalStorageAvailable();

  /**
   * Set item seguro no localStorage
   */
  function safeSetItem(key, value) {
    if (!hasLocalStorage) return false;
    try {
      localStorage.setItem(key, value);
      return true;
    } catch (e) {
      console.warn('LocalStorage full or blocked:', e);
      return false;
    }
  }

  /**
   * Get item seguro do localStorage
   */
  function safeGetItem(key) {
    if (!hasLocalStorage) return null;
    try {
      return localStorage.getItem(key);
    } catch (e) {
      console.warn('LocalStorage error:', e);
      return null;
    }
  }

  /**
   * Remove item seguro do localStorage
   */
  function safeRemoveItem(key) {
    if (!hasLocalStorage) return;
    try {
      localStorage.removeItem(key);
    } catch (e) {
      console.warn('LocalStorage error:', e);
    }
  }

  /**
   * Mostra toast notification
   */
  function showToast(message, type = 'info', duration = 3000) {
    const toast = document.createElement('div');
    toast.className = `udia-toast udia-toast--${type}`;
    toast.textContent = message;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'polite');

    document.body.appendChild(toast);

    setTimeout(() => {
      toast.style.animation = 'slideOutDown 0.3s ease-in';
      setTimeout(() => toast.remove(), 300);
    }, duration);
  }

  /**
   * Valida formulário de review
   */
  function validateReviewForm(form) {
    const content = form.querySelector('[name="content"]');
    const rating = form.querySelector('[name="rating"]:checked');

    // Limpar erros anteriores
    form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
    form.querySelectorAll('.udia-error-message').forEach(el => el.remove());

    const errors = [];

    // Validar rating
    if (!rating) {
      errors.push('Selecione uma avaliação de 1 a 5 estrelas');
    }

    // Validar conteúdo
    if (!content || content.value.trim().length < 10) {
      errors.push('O depoimento deve ter pelo menos 10 caracteres');
      if (content) content.classList.add('error');
    }

    if (content && content.value.trim().length > 2000) {
      errors.push('O depoimento não pode ter mais de 2000 caracteres');
      if (content) content.classList.add('error');
    }

    // Mostrar erros
    if (errors.length > 0) {
      showToast(errors.join('. '), 'error', 4000);
      return false;
    }

    return true;
  }

  // --- Funções do Formulário ---
  async function loadLastOrderProducts(form) {
    try {
      // Cache no lado do cliente (localStorage) pra não ficar batendo no servidor toda hora
      const cacheKey = 'udia_last_order_cache';
      const cacheExpiry = 5 * 60 * 1000; // 5 minutos
      const now = Date.now();

      const cached = safeGetItem(cacheKey);
      if (cached) {
        const parsed = JSON.parse(cached);
        if (now - parsed.timestamp < cacheExpiry) {
          renderProductsFromCache(form, parsed.data);
          return;
        }
      }

      const resp = await fetch(UDIA_REVIEW_V2.ajax_url, {
        method: 'POST',
        credentials: 'same-origin',
        signal: AbortSignal.timeout(10000),
        body: new URLSearchParams({ action: 'udia_v2_fetch_last_order_products', nonce: UDIA_REVIEW_V2.nonce })
      });
      const json = await resp.json();
      const select = form.querySelector('select[name="order_item_id"]');
      const manualWrap = form.querySelector('.udia-manual-product-wrap');
      if (json.success && select) {
        select.innerHTML = json.data.options_html;
        select.disabled = false;
        if (manualWrap) manualWrap.style.display = 'none';

        // Salva o resultado no cache do navegador
        safeSetItem(cacheKey, JSON.stringify({
          timestamp: now,
          data: json.data
        }));
      } else {
        if (select) select.disabled = true;
        if (manualWrap) manualWrap.style.display = 'block';
      }
    } catch (e) {
      console.error('Erro ao buscar pedidos:', e);
      // Fallback para o cache caso a rede falhe
      const cached = safeGetItem(cacheKey);
      if (cached) {
        try {
          const parsed = JSON.parse(cached);
          renderProductsFromCache(form, parsed.data);
        } catch (parseError) {
          console.error('Erro ao parsear cache:', parseError);
        }
      }
    }
  }

  function renderProductsFromCache(form, data) {
    const select = form.querySelector('select[name="order_item_id"]');
    const manualWrap = form.querySelector('.udia-manual-product-wrap');
    if (select && data && data.options_html) {
      select.innerHTML = data.options_html;
      select.disabled = false;
      if (manualWrap) manualWrap.style.display = 'none';
    }
  }

  function updateStarVisual(form) {
    const radios = form.querySelectorAll('.udia-star input[type="radio"]');
    radios.forEach(r => {
      const label = form.querySelector('label[for="' + r.id + '"]');
      if (!label) return;
      if (r.checked) label.classList.add('checked'); else label.classList.remove('checked');
    });
  }

  let isSubmitting = false;
  const SUBMIT_COOLDOWN = 5000; // 5 segundos
  let lastSubmit = 0;

  async function handleFormSubmit(e) {
    if (!e.target || !e.target.classList || !e.target.classList.contains('udia-review-form')) return;
    e.preventDefault();

    // Rate limiting client-side
    const now = Date.now();
    if (now - lastSubmit < SUBMIT_COOLDOWN) {
      showToast('Aguarde alguns segundos antes de enviar novamente', 'warning');
      return;
    }

    if (isSubmitting) return;

    const form = e.target;

    // Validar formulário
    if (!validateReviewForm(form)) {
      return;
    }

    lastSubmit = now;
    isSubmitting = true;

    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
      btn.disabled = true;
      btn.dataset.orig = btn.innerText;
      btn.innerHTML = '<span class="udia-spinner"></span> Enviando...';
    }

    const fd = new FormData(form);
    fd.append('action', 'udia_v2_submit_review');
    fd.append('nonce', UDIA_REVIEW_V2.nonce);

    try {
      const resp = await fetch(UDIA_REVIEW_V2.ajax_url, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        signal: AbortSignal.timeout(10000)
      });

      if (!resp.ok) {
        throw new Error(`HTTP ${resp.status}: ${resp.statusText}`);
      }
      const json = await resp.json();
      if (json.success) {
        const list = document.querySelector('#udia-reviews-list');
        if (list && json.data && json.data.html) {
          const tmp = document.createElement('div');
          tmp.innerHTML = json.data.html;
          const newReview = tmp.firstChild;
          newReview.classList.add('udia-new');
          list.prepend(newReview);
        }
        form.reset();
        updateStarVisual(form);
        if (btn) btn.innerHTML = 'Enviado ✓';

        showToast('Review enviado com sucesso!', 'success');

        // Limpa o cache pra forçar recarregar na próxima vez
        safeRemoveItem('udia_last_order_cache');
      } else {
        const m = json.data && json.data.message ? json.data.message : 'Erro ao enviar';
        showToast(m, 'error');
        if (btn) btn.innerText = btn.dataset.orig || 'Enviar';
      }
    } catch (err) {
      console.error('Submit error:', err);

      let errorMessage = 'Erro inesperado. Tente mais tarde.';

      if (err.name === 'TimeoutError' || err.name === 'AbortError') {
        errorMessage = 'Tempo esgotado. Verifique sua conexão.';
      } else if (err.name === 'TypeError') {
        errorMessage = 'Erro de rede. Tente novamente.';
      } else if (err.message && err.message.startsWith('HTTP')) {
        errorMessage = 'Erro no servidor. Tente mais tarde.';
      }

      showToast(errorMessage, 'error');
      if (btn) btn.innerText = btn.dataset.orig || 'Enviar';
    } finally {
      isSubmitting = false;
      if (btn) {
        btn.disabled = false;
        setTimeout(() => btn.innerText = btn.dataset.orig || 'Enviar', 1200);
      }
    }
  }

  // --- NOVO CARROSSEL: Lógica RESTAURADA ---
  function initCarousel(carousel) {
    const track = carousel.querySelector('.udia-carousel-track');
    const slides = Array.from(track.children);
    const prevButton = carousel.querySelector('.udia-carousel-nav .udia-carousel-btn--prev');
    const nextButton = carousel.querySelector('.udia-carousel-nav .udia-carousel-btn--next');
    const dotsContainer = carousel.querySelector('.udia-carousel-dots');

    if (!track || slides.length === 0) return;

    let currentIndex = 0;
    let isPaused = false;
    let autoplayInterval;
    let resizeTimer;
    let isDragging = false;
    let startPos = 0;
    let currentTranslate = 0;
    let prevTranslate = 0;
    let animationID;

    const getVisibleSlides = () => {
      const width = window.innerWidth;
      if (width >= 1024) return 3;
      if (width >= 768) return 2;
      return 1;
    };

    const updateSlidePositions = () => {
      const slideWidth = slides[0].getBoundingClientRect().width;
      const offset = -slideWidth * currentIndex;
      track.style.transform = 'translateX(' + offset + 'px)';

      if (prevButton) prevButton.disabled = currentIndex === 0;
      if (nextButton) nextButton.disabled = currentIndex >= slides.length - getVisibleSlides();

      if (dotsContainer) {
        const dots = dotsContainer.querySelectorAll('.udia-carousel-dot');
        dots.forEach((dot, index) => {
          dot.classList.toggle('active', index === currentIndex);
        });
      }
    };

    const moveToSlide = (targetIndex) => {
      const maxIndex = Math.max(0, slides.length - getVisibleSlides());
      currentIndex = Math.min(Math.max(0, targetIndex), maxIndex);
      updateSlidePositions();
    };

    const nextSlide = () => {
      if (currentIndex < slides.length - getVisibleSlides()) {
        moveToSlide(currentIndex + 1);
      } else {
        moveToSlide(0);
      }
    };

    const startAutoplay = () => {
      if (autoplayInterval) clearInterval(autoplayInterval);
      autoplayInterval = setInterval(nextSlide, 4000);
    };

    const pauseAutoplay = () => {
      if (autoplayInterval) {
        clearInterval(autoplayInterval);
        autoplayInterval = null;
      }
      isPaused = true;
    };

    const resumeAutoplay = () => {
      if (isPaused) {
        startAutoplay();
        isPaused = false;
      }
    };

    if (prevButton) {
      prevButton.addEventListener('click', (e) => {
        e.preventDefault();
        if (currentIndex > 0) {
          moveToSlide(currentIndex - 1);
          pauseAutoplay();
        }
      });
    }

    if (nextButton) {
      nextButton.addEventListener('click', (e) => {
        e.preventDefault();
        if (currentIndex < slides.length - getVisibleSlides()) {
          moveToSlide(currentIndex + 1);
          pauseAutoplay();
        }
      });
    }

    // Touch events para swipe
    track.addEventListener('touchstart', touchStart, { passive: true });
    track.addEventListener('touchmove', touchMove, { passive: false });
    track.addEventListener('touchend', touchEnd);

    // Mouse events para drag
    track.addEventListener('mousedown', touchStart);
    track.addEventListener('mousemove', touchMove);
    track.addEventListener('mouseup', touchEnd);
    track.addEventListener('mouseleave', () => {
      if (isDragging) {
        touchEnd();
      }
    });

    function touchStart(e) {
      isDragging = true;
      isScrolling = undefined; // Reset scroll direction check
      startPos = getPositionX(e);
      if (!e.type.includes('mouse')) {
        startY = e.touches[0].clientY;
      }

      // CRITICAL: Disable transition during drag to make it 1:1 with finger movement (smooth)
      track.style.transition = 'none';

      animationID = requestAnimationFrame(animation);
      pauseAutoplay();
    }

    function touchMove(e) {
      if (isDragging) {
        const currentPosition = getPositionX(e);

        // Detect vertical scroll vs horizontal swipe
        if (!e.type.includes('mouse')) {
          const currentY = e.touches[0].clientY;
          const diffX = Math.abs(currentPosition - startPos);
          const diffY = Math.abs(currentY - startY);

          // Determine direction if not yet determined
          if (typeof isScrolling === 'undefined') {
            // Wait for a small movement to decide (e.g. 5px)
            if (diffX > 5 || diffY > 5) {
              isScrolling = diffY > diffX;
            }
          }

          // If vertical scrolling, let browser handle it and stop tracking swipe
          if (isScrolling) {
            isDragging = false; // Stop dragging logic
            cancelAnimationFrame(animationID);
            return;
          }

          // If horizontal swipe, prevent default to stop page scroll
          if (e.cancelable && isScrolling === false) {
            e.preventDefault();
          }
        }

        currentTranslate = prevTranslate + currentPosition - startPos;
      }
    }

    function touchEnd() {
      isDragging = false;
      cancelAnimationFrame(animationID);

      const movedBy = currentTranslate - prevTranslate;
      const slideWidth = slides[0].getBoundingClientRect().width;

      // Threshold de 15% para mudar de slide (mais sensível)
      if (movedBy < -0.15 * slideWidth && currentIndex < slides.length - getVisibleSlides()) {
        currentIndex += 1;
      } else if (movedBy > 0.15 * slideWidth && currentIndex > 0) {
        currentIndex -= 1;
      }

      // Re-enable transition for the snap animation
      track.style.transition = 'transform 0.3s ease-out';
      updateSlidePositions();

      setTimeout(() => {
        // Only remove transition if we are not dragging again (edge case)
        if (!isDragging && track) track.style.transition = 'none';
        prevTranslate = currentTranslate;
        resumeAutoplay();
      }, 300);
    }

    function getPositionX(e) {
      if (e.type.includes('mouse')) {
        return e.pageX;
      } else {
        return e.touches[0].clientX;
      }
    }

    function animation() {
      if (isDragging) {
        setSliderPosition();
        requestAnimationFrame(animation);
      }
    }

    function setSliderPosition() {
      track.style.transform = 'translateX(' + currentTranslate + 'px)';
    }

    // Pausar autoplay ao clicar/tocar na área do carrossel
    track.addEventListener('click', () => {
      pauseAutoplay();
    });

    // Inicializar posição e autoplay
    updateSlidePositions();
    startAutoplay();

    // Handle resize with debounce
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => {
        updateSlidePositions();
      }, 250);
    });
  }

  // --- Event Listeners e Inicialização ---
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.udia-review-form').forEach(form => {
      const name = form.querySelector('input[name="name"]');
      if (name) {
        name.readOnly = true;
        name.classList.add('udia-readonly');
      }
      updateStarVisual(form);
      loadLastOrderProducts(form);
    });

    // Inicializa todos os carrosséis
    document.querySelectorAll('.udia-review-carousel-wrap').forEach(initCarousel);
  });

  document.addEventListener('change', function (e) {
    if (e.target && e.target.matches('.udia-review-form .udia-star input[type="radio"]')) {
      const form = e.target.closest('.udia-review-form');
      if (form) updateStarVisual(form);
    }
  }, true);

  document.addEventListener('submit', handleFormSubmit);

})();
