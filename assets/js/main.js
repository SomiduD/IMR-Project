
document.addEventListener("DOMContentLoaded", function() {
    console.log("UtilityOne SL System Loaded");

   
    const logoutLinks = document.querySelectorAll('a[href*="logout.php"]');
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm("Are you sure you want to log out?")) {
                e.preventDefault();
            }
        });
    });

   
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const inputs = form.querySelectorAll('input[type="number"]');
            let valid = true;

            inputs.forEach(input => {
                if (input.value < 0) {
                    alert("Values cannot be negative.");
                    input.style.borderColor = "red";
                    valid = false;
                } else {
                    input.style.borderColor = "#ccc";
                }
            });

            if (!valid) {
                e.preventDefault();
            }
        });
    });

    
    const currentLocation = window.location.pathname;
    const menuItems = document.querySelectorAll('.sidebar a');
    
    menuItems.forEach(item => {
        if (item.href.includes(currentLocation.split('/').pop())) {
            item.classList.add('active');
            item.style.borderLeft = "4px solid #00d2ff";
        }
    });
});