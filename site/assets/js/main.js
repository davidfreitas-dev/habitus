/**
 * Habitus - Global JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
  // Mobile nav toggle
  var navToggle = document.getElementById('navToggle');
  var navLinks = document.getElementById('navLinks');
  if (navToggle && navLinks) {
    navToggle.addEventListener('click', function() {
      navLinks.classList.toggle('open');
    });
  }

  // Close nav on link click
  if (navLinks) {
    document.querySelectorAll('.nav__links a').forEach(function(link) {
      link.addEventListener('click', function() {
        navLinks.classList.remove('open');
      });
    });
  }

  // Scroll animations (fade-in observer)
  var fadeInElements = document.querySelectorAll('.fade-in');
  if (fadeInElements.length > 0 && typeof IntersectionObserver !== 'undefined') {
    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
        }
      });
    }, { threshold: 0.1 });

    fadeInElements.forEach(function(el) {
      observer.observe(el);
    });
  }
});

/**
 * Switch tabs on the Privacy Policy / Terms of Use page.
 * Needs to be in global scope because it is called via inline onclick.
 * 
 * @param {string} tab - The tab identifier (e.g., 'privacy' or 'terms')
 */
function switchTab(tab) {
  // Update buttons active states
  document.querySelectorAll('.tabs__button').forEach(function(btn) {
    btn.classList.remove('active');
    btn.setAttribute('aria-selected', 'false');
  });
  
  var targetTab = document.getElementById('tab-' + tab);
  if (targetTab) {
    targetTab.classList.add('active');
    targetTab.setAttribute('aria-selected', 'true');
  }

  // Update panels active states
  document.querySelectorAll('.panel').forEach(function(panel) {
    panel.classList.remove('active');
  });
  
  var targetPanel = document.getElementById('panel-' + tab);
  if (targetPanel) {
    targetPanel.classList.add('active');
  }
}
