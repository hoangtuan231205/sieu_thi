/**
 * =============================================================================
 * FRESHMART - MAIN JAVASCRIPT
 * =============================================================================
 */

// =============================================================================
// 1. KHỞI TẠO - Chạy khi trang load xong
// =============================================================================

document.addEventListener('DOMContentLoaded', function () {

    // Khởi tạo các components (CHỈ DESKTOP)
    initScrollToTop();
    initDropdownMenus();
    initSearchFocus();
    initLazyLoading();
    initUserDropdown();

    console.log('✅ FreshMart khởi tạo thành công!');

});

// =============================================================================
// 2. NÚT LÊN ĐẦU TRANG - Nút cuộn lên đầu
// =============================================================================

function initScrollToTop() {
    const scrollBtn = document.getElementById('scrollToTop');

    if (!scrollBtn) return;

    // Hiện/ẩn button khi cuộn
    window.addEventListener('scroll', function () {
        if (window.pageYOffset > 300) {
            scrollBtn.classList.add('show');
        } else {
            scrollBtn.classList.remove('show');
        }
    });

    // Cuộn lên đầu khi click
    scrollBtn.addEventListener('click', function () {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// =============================================================================
// 4. MENU DROPDOWN - Xử lý menu thả xuống (Desktop)
// =============================================================================

function initDropdownMenus() {
    const dropdownItems = document.querySelectorAll('.has-dropdown');

    dropdownItems.forEach(item => {
        const dropdownMenu = item.querySelector('.dropdown-menu');

        if (!dropdownMenu) return;

        // Hàm tính toán và cập nhật vị trí dropdown
        function updateDropdownPosition() {
            const rect = item.getBoundingClientRect();
            const left = rect.left;
            const top = rect.bottom;

            // Set CSS variables for fixed positioning
            item.style.setProperty('--dropdown-left', `${left}px`);
            item.style.setProperty('--dropdown-top', `${top}px`);
        }

        // Thêm animation khi hover
        item.addEventListener('mouseenter', function () {
            // Cập nhật vị trí trước khi hiển thị
            updateDropdownPosition();

            dropdownMenu.style.display = 'block';

            // Kích hoạt animation
            setTimeout(() => {
                dropdownMenu.style.opacity = '1';
                dropdownMenu.style.visibility = 'visible';
                dropdownMenu.style.transform = 'translateY(0)';
            }, 10);
        });

        item.addEventListener('mouseleave', function () {
            dropdownMenu.style.opacity = '0';
            dropdownMenu.style.visibility = 'hidden';
            dropdownMenu.style.transform = 'translateY(-10px)';

            setTimeout(() => {
                if (dropdownMenu.style.opacity === '0') {
                    dropdownMenu.style.display = 'none';
                }
            }, 300);
        });

        // Cập nhật vị trí khi scroll và resize
        window.addEventListener('scroll', updateDropdownPosition, { passive: true });
        window.addEventListener('resize', updateDropdownPosition, { passive: true });
    });
}

// =============================================================================
// 5. FOCUS TÌM KIẾM - Animation cho ô tìm kiếm
// =============================================================================

function initSearchFocus() {
    const searchInput = document.querySelector('.search-input');

    if (!searchInput) return;

    searchInput.addEventListener('focus', function () {
        this.parentElement.style.transform = 'scale(1.02)';
    });

    searchInput.addEventListener('blur', function () {
        this.parentElement.style.transform = 'scale(1)';
    });
}

// =============================================================================
// 6. DROPDOWN NGƯỜI DÙNG - Click để toggle menu profile
// =============================================================================

function initUserDropdown() {
    // Hỗ trợ cả .user-dropdown và .user-menu.dropdown
    const userDropdown = document.querySelector('.user-dropdown, .user-menu.dropdown');

    if (!userDropdown) return;

    const actionBtn = userDropdown.querySelector('.action-btn');
    const dropdownMenu = userDropdown.querySelector('.dropdown-menu');

    if (!actionBtn || !dropdownMenu) return;

    // Toggle dropdown khi click
    actionBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const isVisible = dropdownMenu.style.display === 'block';

        if (isVisible) {
            dropdownMenu.style.display = 'none';
        } else {
            dropdownMenu.style.display = 'block';
        }
    });

    // Đóng dropdown khi click bên ngoài
    document.addEventListener('click', function (e) {
        if (!userDropdown.contains(e.target)) {
            dropdownMenu.style.display = 'none';
        }
    });

    // Ngăn dropdown đóng khi click bên trong
    dropdownMenu.addEventListener('click', function (e) {
        e.stopPropagation();
    });
}

// =============================================================================
// 7. THÊM VÀO GIỎ HÀNG - Xử lý thêm sản phẩm vào giỏ
// =============================================================================

// =============================================================================
// 15. MUA NGAY - Thêm vào giỏ và checkout ngay
// =============================================================================

function buyNow(productId, quantity = 1) {
    const csrfToken = document.querySelector('meta[name="csrf_token"]')?.content || '';
    const baseUrl = document.querySelector('meta[name="base_url"]')?.content || '';

    // URL Construction Fix
    let url = baseUrl + '/cart/buyNow'; // An toàn: dùng đúng tên method

    // Remove /public if it's already in baseUrl to avoid double slash
    if (baseUrl.endsWith('/public')) {
        url = baseUrl.replace(/\/public$/, '') + '/public/cart/buyNow';
    }

    // FIX: Remove hardcoded quantity override
    // quantity = 1; <--- DELETE THIS

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    formData.append('csrf_token', csrfToken);

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = baseUrl + '/checkout';
            } else {
                if (typeof showNotification === 'function') {
                    showNotification(data.message || 'Có lỗi xảy ra!', 'error');
                } else {
                    alert(data.message || 'Có lỗi xảy ra!');
                }
            }
        })
        .catch(error => {
            console.error('Lỗi Mua Ngay:', error);
            alert('Có lỗi xảy ra. Vui lòng thử lại.');
        });
}

// Cập nhật addToCart tương tự để đảm bảo URL chính xác
function addToCart(productId, quantity = 1) {
    const csrfToken = document.querySelector('meta[name="csrf_token"]')?.content || '';
    let baseUrl = document.querySelector('meta[name="base_url"]')?.content || '';

    // URL Construction Fix
    let url = baseUrl + '/cart/add';

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    formData.append('csrf_token', csrfToken);

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartBadge(data.cart_count);
                showNotification('Đã thêm vào giỏ hàng!', 'success');
                animateCartButton();
            } else {
                showNotification(data.message || 'Có lỗi xảy ra!', 'error');
            }
        })
        .catch(error => {
            console.error('Lỗi:', error);
            showNotification('Không thể thêm vào giỏ hàng!', 'error');
        });
}


// =============================================================================
// 8. CẬP NHẬT BADGE GIỎ HÀNG - Cập nhật số lượng hiển thị
// =============================================================================

function updateCartBadge(count) {
    const cartBadge = document.querySelector('.cart-badge');
    if (cartBadge) {
        cartBadge.textContent = count;
        // Animation bằng CSS class
        cartBadge.classList.add('cart-updated');
        setTimeout(() => {
            cartBadge.classList.remove('cart-updated');
        }, 500);
    }
}

// =============================================================================
// 9. ANIMATION GIỎ HÀNG - Hiệu ứng khi thêm vào giỏ
// =============================================================================

function animateCartButton() {
    const cartBtn = document.querySelector('.cart-btn');
    if (cartBtn) {
        cartBtn.style.animation = 'pulse 0.5s ease';
        setTimeout(() => {
            cartBtn.style.animation = '';
        }, 500);
    }
}

// =============================================================================
// 10. SHOW NOTIFICATION - Hiển thị thông báo (Toast)
// =============================================================================

function showNotification(message, type = 'success') {
    // Kiểm tra container có tồn tại không, nếu chưa thì tạo mới
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
        document.body.appendChild(container); // Fix: Append to body
    }

    const toast = document.createElement('div');
    toast.className = `toast-message toast-${type}`;
    toast.style.cssText = `
        background: ${type === 'success' ? '#4CAF50' : '#F44336'};
        color: white;
        padding: 15px 25px;
        margin-bottom: 10px;
        border-radius: 4px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        animation: slideIn 0.3s ease-out forwards;
        min-width: 250px;
    `;

    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}" style="margin-right: 10px;"></i>
        <span>${message}</span>
    `;

    container.appendChild(toast);

    // Tự động xóa sau 3 giây
    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s ease-in forwards';
        setTimeout(() => {
            if (toast.parentElement) {
                toast.parentElement.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

// =============================================================================
// 11. CÁC HÀM TIỆN ÍCH KHÁC
// =============================================================================

function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(price);
}

function initLazyLoading() {
    const lazyImages = document.querySelectorAll('img[data-src]');
    if (lazyImages.length === 0) return;

    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('skeleton');
                observer.unobserve(img);
            }
        });
    });

    lazyImages.forEach(img => imageObserver.observe(img));
}

// =============================================================================
// 14. SLIDER BANNER - Slider tự động cho banner
// =============================================================================

let currentSlide = 0;
let slideInterval;

function initHeroSlider() {
    const slides = document.querySelectorAll('.hero-slide-full');
    const dots = document.querySelectorAll('.dot');
    const container = document.querySelector('.slider-container');

    if (slides.length <= 1) return;

    // console.log('🎡 FreshMart Slider đã khởi tạo - 3 giây/slide');

    function showSlide(index) {
        // Xóa class active ở slide hiện tại
        slides[currentSlide].classList.remove('active');
        if (dots[currentSlide]) dots[currentSlide].classList.remove('active');

        // Tính index mới
        currentSlide = (index + slides.length) % slides.length;

        // Thêm class active cho slide mới
        slides[currentSlide].classList.add('active');
        if (dots[currentSlide]) dots[currentSlide].classList.add('active');
    }

    // Các hàm global để HTML gọi được (onclick)
    window.nextSlide = function () {
        showSlide(currentSlide + 1);
    };

    window.prevSlide = function () {
        showSlide(currentSlide - 1);
    };

    window.goToSlide = function (index) {
        if (index === currentSlide) return;
        showSlide(index);
        resetTimer();
    };

    function startTimer() {
        stopTimer();
        slideInterval = setInterval(window.nextSlide, 3000);
    }

    function stopTimer() {
        if (slideInterval) clearInterval(slideInterval);
    }

    function resetTimer() {
        stopTimer();
        startTimer();
    }

    // Tạm dừng khi hover
    if (container) {
        container.addEventListener('mouseenter', stopTimer);
        container.addEventListener('mouseleave', startTimer);
    }

    // Chạy slide
    startTimer();
}

// Initialize things that were lost
document.addEventListener('DOMContentLoaded', function () {
    initLazyLoading();
    if (typeof initHeroSlider === 'function') {
        initHeroSlider();
    }
});

// Gán vào window để HTML có thể gọi
window.buyNow = buyNow;
window.addToCart = addToCart;
window.showNotification = showNotification;