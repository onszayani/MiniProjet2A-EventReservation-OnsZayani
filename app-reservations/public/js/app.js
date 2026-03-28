// Smooth scroll vers la section au chargement si un hash est présent dans l'URL
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash) {
        // Attendre un court instant pour que la page soit complètement chargée
        setTimeout(function() {
            const targetId = window.location.hash.substring(1);
            const targetElement = document.getElementById(targetId);

            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }, 100);
    }

    // Gérer le smooth scroll pour les liens de navigation
    const navLinks = document.querySelectorAll('.nav-links a[href^="#"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);

            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

                history.pushState(null, null, '#' + targetId);
            }
        });
    });
});

// hero slider
let currentSlide = 0;
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('.slider-dot');

function showSlide(n) {
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));

    currentSlide = (n + slides.length) % slides.length;
    slides[currentSlide].classList.add('active');
    dots[currentSlide].classList.add('active');
}

function nextSlide() {
    showSlide(currentSlide + 1);
}

setInterval(nextSlide, 5000);

dots.forEach(dot => {
    dot.addEventListener('click', () => {
        showSlide(parseInt(dot.dataset.slide));
    });
});


// filtre events
const filterButtons = document.querySelectorAll('.filter-btn');
const eventCards = document.querySelectorAll('.event-card');

filterButtons.forEach(button => {
    button.addEventListener('click', () => {
        const filter = button.dataset.filter;

        filterButtons.forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');

        eventCards.forEach(card => {
            card.style.display =
                (filter === 'all' || card.dataset.category === filter)
                    ? 'block'
                    : 'none';
        });
    });
});


// rating
const stars = document.querySelectorAll('.star');
const ratingInput = document.getElementById('ratingInput');

if (stars.length && ratingInput) {
    stars.forEach(star => {
        star.addEventListener('click', () => {
            const rating = parseInt(star.dataset.rating);
            ratingInput.value = rating;

            stars.forEach(s => {
                s.classList.toggle('active', s.dataset.rating <= rating);
            });
        });
    });
}


//star counting
function animateCounter(el) {
    const target = parseInt(el.dataset.target);
    let current = 0;
    const step = target / 120;

    const timer = setInterval(() => {
        current += step;
        if (current >= target) {
            el.textContent = target + (el.nextElementSibling.textContent.includes('Rate') ? '%' : '+');
            clearInterval(timer);
        } else {
            el.textContent = Math.floor(current);
        }
    }, 16);
}

const statsSection = document.querySelector('.stats-section');
if (statsSection) {
    const observer = new IntersectionObserver(entries => {
        if (entries[0].isIntersecting) {
            statsSection.querySelectorAll('.stat-number').forEach(animateCounter);
            observer.disconnect();
        }
    }, { threshold: 0.5 });

    observer.observe(statsSection);
}


// show more reviews
const loadMoreBtn = document.getElementById('loadMoreReviews');
const hiddenReviews = document.querySelectorAll('.hidden-review');
let currentIndex = 0;
const STEP = 3;

if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', () => {
        let shown = 0;

        for (let i = currentIndex; i < hiddenReviews.length && shown < STEP; i++) {
            hiddenReviews[i].classList.remove('hidden-review');
            shown++;
            currentIndex++;
        }

        // Si tout est affiché → cacher le bouton
        if (currentIndex >= hiddenReviews.length) {
            loadMoreBtn.style.display = 'none';
        }
    });
}

