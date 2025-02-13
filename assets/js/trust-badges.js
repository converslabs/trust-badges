document.addEventListener('DOMContentLoaded', function() {
    // Function to handle badge animations
    function animateBadges() {
        const containers = document.querySelectorAll('.trust-badges-container');
        
        containers.forEach(container => {
            const animation = container.dataset.animation;
            if (!animation) return;
            
            // Reset animations
            container.querySelectorAll('.trust-badge').forEach((badge, index) => {
                badge.style.animation = 'none';
                badge.offsetHeight; // Trigger reflow
                badge.style.animation = null;
                badge.style.animationDelay = `${index * 0.1}s`; // Stagger effect
            });
        });
    }
    
    // Run animation on page load
    animateBadges();
    
    // Optional: Run animation when badges come into view
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateBadges();
                }
            });
        });
        
        document.querySelectorAll('.trust-badges-container').forEach(container => {
            observer.observe(container);
        });
    }
}); 