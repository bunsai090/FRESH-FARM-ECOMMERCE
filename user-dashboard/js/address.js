// Modal Management
const showModal = (modalId) => {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        modal.classList.add('active');
    }
};

const hideModal = (modalId) => {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }, 300); // Match this with CSS transition duration
    }
};

// Address Management Functions
const showDeleteConfirmation = (addressId) => {
    const modal = document.getElementById('confirmationModal');
    const confirmBtn = modal.querySelector('.confirm-delete');
    
    confirmBtn.onclick = () => deleteAddress(addressId);
    showModal('confirmationModal');
};

const deleteAddress = async (addressId) => {
    try {
        const confirmationModal = document.getElementById('confirmationModal');
        const deleteButton = confirmationModal.querySelector('.confirm-delete');
        const buttonText = deleteButton.querySelector('.button-text');
        const loadingSpinner = deleteButton.querySelector('.loading-spinner');
        
        // Show loading state
        deleteButton.disabled = true;
        buttonText.style.opacity = '0.5';
        loadingSpinner.style.display = 'block';

        const response = await fetch('delete_address.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ address_id: addressId })
        });

        const data = await response.json();

        if (data.status === 'success') {
            hideModal('confirmationModal');
            
            // Remove the address card from DOM with animation
            const addressCard = document.querySelector(`[data-address-id="${addressId}"]`);
            if (addressCard) {
                addressCard.style.transition = 'all 0.3s ease';
                addressCard.style.opacity = '0';
                addressCard.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    addressCard.remove();
                    
                    // Update UI if no addresses left
                    const remainingAddresses = document.querySelectorAll('.address-card');
                    if (remainingAddresses.length === 0) {
                        const addressContainer = document.querySelector('.address-container');
                        if (addressContainer) {
                            const noAddressMessage = document.createElement('div');
                            noAddressMessage.className = 'no-address-message';
                            noAddressMessage.style.opacity = '0';
                            noAddressMessage.innerHTML = `
                                <p>You haven't added any addresses yet.</p>
                                <button class="add-address-btn" onclick="showModal('addressModal')">
                                    <i class="fas fa-plus"></i> Add New Address
                                </button>
                            `;
                            addressContainer.appendChild(noAddressMessage);
                            
                            // Trigger reflow for animation
                            noAddressMessage.offsetHeight;
                            noAddressMessage.style.transition = 'opacity 0.3s ease';
                            noAddressMessage.style.opacity = '1';
                        }
                    }
                }, 300);
            }

            // Show success message
            showModal('successModal');
            setTimeout(() => {
                hideModal('successModal');
            }, 2000);
        } else {
            throw new Error(data.message || 'Error deleting address');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to delete address. Please try again.');
    } finally {
        const deleteButton = document.querySelector('#confirmationModal .confirm-delete');
        const buttonText = deleteButton.querySelector('.button-text');
        const loadingSpinner = deleteButton.querySelector('.loading-spinner');
        
        // Reset button state
        deleteButton.disabled = false;
        buttonText.style.opacity = '1';
        loadingSpinner.style.display = 'none';
    }
};

const setDefaultAddress = async (addressId) => {
    try {
        const response = await fetch('set_default_address.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ address_id: addressId })
        });

        const data = await response.json();

        if (data.status === 'success') {
            // Remove existing default badges
            document.querySelectorAll('.default-badge').forEach(badge => {
                badge.remove();
            });

            // Add default badge to new default address
            const addressCard = document.querySelector(`[data-address-id="${addressId}"]`);
            if (addressCard) {
                const addressType = addressCard.querySelector('.address-type');
                const defaultBadge = document.createElement('span');
                defaultBadge.className = 'default-badge';
                defaultBadge.textContent = 'Default';
                addressType.appendChild(defaultBadge);
            }

            showModal('successModal');
        } else {
            throw new Error(data.message || 'Error setting default address');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to set default address. Please try again.');
    }
};

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Close modals when clicking outside
    document.querySelectorAll('.confirmation-overlay, .modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                hideModal(overlay.id);
            }
        });
    });

    // Prevent event bubbling for modal content
    document.querySelectorAll('.modal-content, .confirmation-modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    });

    // Close modals on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.confirmation-overlay, .modal-overlay').forEach(modal => {
                if (modal.style.display === 'flex') {
                    hideModal(modal.id);
                }
            });
        }
    });

    // Success modal auto-close
    const successModal = document.getElementById('successModal');
    if (successModal) {
        setTimeout(() => {
            hideModal('successModal');
        }, 2000);
    }
}); 