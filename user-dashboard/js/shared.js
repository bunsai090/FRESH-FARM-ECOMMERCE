// Logout functionality
function showLogoutModal() {
    const modal = document.getElementById('logoutModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function hideLogoutModal() {
    const modal = document.getElementById('logoutModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function confirmLogout(button) {
    if (!button) return;

    button.disabled = true;
    button.querySelector('.btn-text').style.display = 'none';
    button.querySelector('.loading-spinner').style.display = 'inline';

    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'logout'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            window.location.href = '../index.php';
        } else {
            throw new Error(data.message || 'Error during logout');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        button.disabled = false;
        button.querySelector('.btn-text').style.display = 'inline';
        button.querySelector('.loading-spinner').style.display = 'none';
    });
}

// Handle modal clicks
document.addEventListener('DOMContentLoaded', function() {
    const logoutModal = document.getElementById('logoutModal');
    
    // Close modal when clicking outside
    if (logoutModal) {
        logoutModal.addEventListener('click', function(e) {
            if (e.target === this) {
                hideLogoutModal();
            }
        });
    }

    // Prevent event bubbling for modal content
    document.querySelectorAll('.logout-modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideLogoutModal();
        }
    });
}); 