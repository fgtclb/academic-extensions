(function () {
  const MOBILE_BREAKPOINT = 768;

  class StudyPlan {
    constructor(container) {
      this.container = container;
      this.modules = this.container.querySelectorAll('.module');
      this.headers = this.container.querySelectorAll('.header');

      this.init();
    }

    init() {
      this.buildCategoryFilter();
      this.initCategoryFilter();
      this.initModuleClicks();
      this.initModal();
      this.handleResize();
      this.initHeaderClicks();
    }

    buildCategoryFilter() {
      const filterList = this.container.querySelector('.filter');
      const filterTemplate = this.container.querySelector('.filter li')?.outerHTML;

      if (!filterList || !filterTemplate) return;

      filterList.innerHTML = '';

      const categoriesMap = new Map();

      this.container.querySelectorAll('.module[data-categories]').forEach(module => {
        const categoriesData = module.dataset.categories;
        if (!categoriesData || categoriesData === '[]') return;

        try {
          const categories = JSON.parse(categoriesData);
          categories.forEach(cat => {
            if (cat.uid && !categoriesMap.has(cat.uid)) {
              categoriesMap.set(cat.uid, {
                uid: cat.uid,
                label: cat.label,
                colour: cat.colour
              });
            }
          });
        } catch (e) { }
      });

      if (categoriesMap.size === 0) {
        return;
      }

      Array.from(categoriesMap.values()).forEach(category => {
        let template = filterTemplate;

        template = template.replace(/category-id-placeholder/g, `${category.uid}`);
        template = template.replace(/category-color-placeholder/g, `${category.colour}`);
        template = template.replace(/category-label-placeholder/g, `${category.label}`);

        let temp = document.createElement('li');
        temp.innerHTML = template;

        filterList.appendChild(temp.firstElementChild);
      });
    }

    getModuleCategoryIds(module) {
      const categoriesData = module.dataset.categories;
      if (!categoriesData || categoriesData === '[]') return [];

      try {
        const categories = JSON.parse(categoriesData);
        return categories.map(cat => String(cat.uid));
      } catch (e) {
        return [];
      }
    }

    initCategoryFilter() {
      this.container.querySelectorAll('.filter button').forEach(button => {
        button.addEventListener('click', (e) => {
          if (button.classList.contains('highlighted')) {
            this.clearHighlights();
          } else {
            this.highlightCategory(button.dataset.categoryId, button.dataset.categoryColor);
            button.classList.add('highlighted');
          }
        });

        button.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();

            if (button.classList.contains('highlighted')) {
              this.clearHighlights();
            } else {
              this.highlightCategory(button.dataset.categoryId, button.dataset.categoryColor);
              button.classList.add('highlighted');
            }
          }
        });
      });
    }

    highlightCategory(categoryId, color) {
      this.clearHighlights();
      this.container.classList.add('highlighted');

      this.modules.forEach(module => {
        const categoryIds = this.getModuleCategoryIds(module);

        if (categoryIds.includes(categoryId)) {
          module.classList.add('highlighted');
          module.closest('.col').classList.add('highlighted', 'open');
          this.container.style.setProperty('--highlight-color', this.hexToRgba(color, 0.25));
        }
      });
    }

    clearHighlights() {
      this.container.querySelectorAll('.highlighted').forEach(highlighted => {
        highlighted.classList.remove('highlighted', 'open');
      });
      this.container.classList.remove('highlighted');
    }

    initHeaderClicks() {
      this.headers.forEach(header => {
        header.addEventListener('click', () => {
          if (window.innerWidth <= MOBILE_BREAKPOINT) {
            header.closest('.col').classList.toggle('open');
            header.setAttribute('aria-expanded', header.closest('.col').classList.contains('open'));
          }
        });

        header.addEventListener('keydown', (e) => {
          if (window.innerWidth <= MOBILE_BREAKPOINT) {
            if (e.key === 'Enter' || e.key === ' ') {
              e.preventDefault();
              header.closest('.col').classList.toggle('open');
              header.setAttribute('aria-expanded', header.closest('.col').classList.contains('open'));
            }
          }
        });
      });
    }

    enableMobile() {
      this.headers.forEach(header => {
        header.style.height = '';
        header.setAttribute('aria-expanded', 'false');
        header.setAttribute('tabindex', '0');
        header.setAttribute('role', 'button');
        header.setAttribute('aria-hidden', 'false');
        header.removeAttribute('inert');
      });

      this.modules.forEach(module => {
        module.style.height = '';
      });
    }

    disableMobile() {
      let headerHeight = 0;
      let moduleHeight = 0;

      this.headers.forEach(header => {
        headerHeight = Math.max(headerHeight, header.offsetHeight);
      });

      this.headers.forEach(header => {
        header.style.height = `${headerHeight}px`;
        header.setAttribute('tabindex', '-1');
        header.setAttribute('role', '');
        header.setAttribute('aria-hidden', 'true');
        header.setAttribute('inert', '');
      });

      this.modules.forEach(module => {
        moduleHeight = Math.max(moduleHeight, module.offsetHeight);
      });

      this.modules.forEach(module => {
        module.style.height = `${moduleHeight}px`;
      });
    }

    initModuleClicks() {
      this.container.querySelectorAll('.modal-trigger').forEach(module => {
        module.addEventListener('click', () => document.getElementById(module.dataset.dialogId)?.showModal());

        module.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            document.getElementById(module.dataset.dialogId)?.showModal()
          }
        });
      });
    }

    initModal() {
      document.querySelectorAll('.module dialog').forEach(dialog => {
        const button = dialog.querySelector('button');

        button.addEventListener('click', (e) => {
          this.closeModal(dialog)
          e.stopPropagation();
        });

        button.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.closeModal(dialog)
            e.stopPropagation();
          }
        });
      });
    }

    closeModal(dialog) {
      const audioElements = dialog.querySelectorAll('audio');

      audioElements.forEach(audio => {
        audio.pause();
        audio.currentTime = 0;
      });

      dialog.close();
    }

    hexToRgba(hex, alpha) {
      if (!hex || hex.length < 7) return `rgba(0, 0, 0, ${alpha})`;
      const r = parseInt(hex.slice(1, 3), 16);
      const g = parseInt(hex.slice(3, 5), 16);
      const b = parseInt(hex.slice(5, 7), 16);
      return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    handleResize() {
      if (window.innerWidth > MOBILE_BREAKPOINT) {
        this.disableMobile();
      } else {
        this.enableMobile();
      }
    }
  }

  const studyPlanInstances = new Map();

  function init() {
    document.querySelectorAll('.academic-study-plan').forEach(container => {
      const id = container.dataset.studyPlan;

      if (!studyPlanInstances.has(id)) {
        studyPlanInstances.set(id, new StudyPlan(container));
      }
    });
  }

  function handleResize() {
    studyPlanInstances.forEach(instance => instance.handleResize());
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  let resizeTimeout;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(handleResize, 150);
  });
})();
