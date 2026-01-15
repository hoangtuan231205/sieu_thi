// ===========================
// CÁC HÀM THÔNG BÁO
// ===========================

function showNotification(message) {
    const notification = document.getElementById('notification');
    const messageEl = document.getElementById('notification-message');

    if (!notification || !messageEl) return;

    messageEl.textContent = message;
    notification.classList.remove('hidden');
    notification.classList.add('notification-enter');

    setTimeout(() => {
        notification.classList.add('hidden');
        notification.classList.remove('notification-enter');
    }, 3000);
}

// ===========================
// CÁC HÀM MODAL SẢN PHẨM
// ===========================

function openAddModal() {
    const modal = document.getElementById('productModal');
    const form = document.getElementById('productForm');
    const header = document.getElementById('modalHeader');
    const title = document.getElementById('modalTitle');

    if (!modal || !form) return;

    modal.classList.remove('hidden');
    title.textContent = 'Thêm sản phẩm mới';
    header.classList.remove('bg-blue-500', 'text-white');
    title.classList.remove('text-white');

    // Xác định đường dẫn admin động (hoạt động khi app không ở gốc host)
    const adminBase = window.location.pathname.split('/admin')[0] + '/admin';
    // ✅ FIX: Set form action cho add
    form.action = adminBase + '/product-add';
    form.reset();

    document.getElementById('product_id').value = '';
    document.getElementById('stock').value = '0';
    document.getElementById('charCount').textContent = '0';
    document.getElementById('currentImage').classList.add('hidden');
}

function openEditModal(product) {
    const modal = document.getElementById('productModal');
    const form = document.getElementById('productForm');
    const header = document.getElementById('modalHeader');
    const title = document.getElementById('modalTitle');

    if (!modal || !form || !product) return;

    modal.classList.remove('hidden');
    title.textContent = 'Chỉnh sửa sản phẩm';
    header.classList.add('bg-blue-500', 'text-white');
    title.classList.add('text-white');

    // Xác định đường dẫn admin động (hoạt động khi app không ở gốc host)
    const adminBase = window.location.pathname.split('/admin')[0] + '/admin';
    // ✅ FIX: Set form action cho edit
    form.action = adminBase + '/product-edit/' + product.ID_sp;

    // Điền các trường form
    document.getElementById('product_id').value = product.ID_sp;
    document.getElementById('name').value = product.Ten || '';
    document.getElementById('category_id').value = product.ID_danh_muc || '';
    document.getElementById('price').value = product.Gia_tien || '';
    document.getElementById('stock').value = product.So_luong_ton || 0;
    document.getElementById('unit').value = product.Don_vi_tinh || '';
    document.getElementById('origin').value = product.Xuat_xu || '';
    document.getElementById('status').value = product.Trang_thai || 'active';
    document.getElementById('sku').value = product.Ma_hien_thi || '';
    document.getElementById('description').value = product.Mo_ta_sp || '';

    // Cập nhật số đếm ký tự
    const descLength = (product.Mo_ta_sp || '').length;
    document.getElementById('charCount').textContent = descLength;

    // Hiển thị hình ảnh hiện tại nếu có
    if (product.Hinh_anh) {
        document.getElementById('currentImage').classList.remove('hidden');
        document.getElementById('currentImagePreview').src = window.location.pathname.split('/admin')[0] + '/assets/img/products/' + product.Hinh_anh;
    } else {
        document.getElementById('currentImage').classList.add('hidden');
    }
}

function closeProductModal() {
    const modal = document.getElementById('productModal');
    const title = document.getElementById('modalTitle');

    if (modal) modal.classList.add('hidden');
    if (title) title.classList.remove('text-white');
}

// ===========================
// CÁC HÀM MODAL XÓA
// ===========================

function openDeleteModal(id, name, code) {
    const modal = document.getElementById('deleteModal');

    if (!modal) return;

    modal.classList.remove('hidden');

    // Đảm bảo form xóa gửi đến đúng đường dẫn admin
    const adminBase = window.location.pathname.split('/admin')[0] + '/admin';
    const deleteForm = document.getElementById('deleteForm');
    if (deleteForm) deleteForm.action = adminBase + '/product-delete';

    document.getElementById('delete_product_id').value = id;
    document.getElementById('deleteProductName').textContent = '"' + name + '"';
    document.getElementById('deleteProductCode').textContent = '(' + code + ')';
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    if (modal) modal.classList.add('hidden');
}
// ===========================
// XÓA AJAX SUBMIT
// ===========================

function submitDelete(e) {
    e.preventDefault();

    const productId = document.getElementById('delete_product_id')?.value;
    if (!productId) {
        alert('Không xác định được sản phẩm');
        return;
    }

    const adminBase = window.location.pathname.split('/admin')[0] + '/admin';

    fetch(adminBase + '/product-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest' // 🔥 BẮT BUỘC
        },
        body: 'product_id=' + encodeURIComponent(productId)
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeDeleteModal();
                showNotification(data.message || 'Xóa sản phẩm thành công');
                setTimeout(() => location.reload(), 800);
            } else {
                alert(data.message || 'Xóa sản phẩm thất bại');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Có lỗi xảy ra khi xóa sản phẩm');
        });
}
// ===========================
// QUẢN LÝ TỒN KHO
// ===========================

function incrementStock() {
    const input = document.getElementById('stock');
    if (input) {
        input.value = parseInt(input.value || 0) + 1;
    }
}

function decrementStock() {
    const input = document.getElementById('stock');
    if (input) {
        const currentValue = parseInt(input.value || 0);
        if (currentValue > 0) {
            input.value = currentValue - 1;
        }
    }
}

// ===========================
// TẢI ẢNH LÊN
// ===========================

function handleImageUpload(e) {
    const file = e.target.files[0];
    if (!file) return;

    // Kiểm tra loại file
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!validTypes.includes(file.type)) {
        alert('Vui lòng chọn file ảnh hợp lệ (JPEG, PNG, GIF, WEBP)');
        e.target.value = '';
        return;
    }

    // Kiểm tra kích thước file (tối đa 5MB)
    if (file.size > 5 * 1024 * 1024) {
        alert('Kích thước file không được vượt quá 5MB');
        e.target.value = '';
        return;
    }

    // Hiển thị xem trước
    const reader = new FileReader();
    reader.onload = function (e) {
        const currentImage = document.getElementById('currentImage');
        const preview = document.getElementById('currentImagePreview');

        if (currentImage && preview) {
            currentImage.classList.remove('hidden');
            preview.src = e.target.result;
        }
    };
    reader.readAsDataURL(file);
}

// ===========================
// ĐẼM KÝ TỰ
// ===========================

function updateCharCount() {
    const textarea = document.getElementById('description');
    const counter = document.getElementById('charCount');

    if (!textarea || !counter) return;

    const maxLength = 2000;
    const currentLength = textarea.value.length;

    counter.textContent = currentLength;

    if (currentLength > maxLength) {
        counter.classList.add('text-red-500');
        textarea.value = textarea.value.substring(0, maxLength);
    } else {
        counter.classList.remove('text-red-500');
    }
}

// ===========================
// KIỂM TRA FORM
// ===========================

function validateProductForm(e) {
    const name = document.getElementById('name').value.trim();
    const price = document.getElementById('price').value;
    const category = document.getElementById('category_id').value;

    if (!name) {
        alert('Vui lòng nhập tên sản phẩm');
        e.preventDefault();
        return false;
    }

    if (!price || parseFloat(price) < 0) {
        alert('Vui lòng nhập giá bán hợp lệ');
        e.preventDefault();
        return false;
    }

    if (!category) {
        alert('Vui lòng chọn danh mục');
        e.preventDefault();
        return false;
    }

    return true;
}

// ===========================
// KÉO THẢ TẢI ẢNH LÊN
// ===========================

function setupDragAndDrop() {
    const uploadArea = document.querySelector('.border-dashed');
    const fileInput = document.getElementById('image');

    if (!uploadArea || !fileInput) return;

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => {
            uploadArea.classList.add('border-blue-500', 'bg-blue-50');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => {
            uploadArea.classList.remove('border-blue-500', 'bg-blue-50');
        }, false);
    });

    uploadArea.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;

        if (files.length > 0) {
            fileInput.files = files;
            handleImageUpload({ target: fileInput });
        }
    }, false);
}

// ===========================
// XỬ LÝ SỰ KIỆN
// ===========================

document.addEventListener('DOMContentLoaded', function () {
    // Đếm ký tự
    const descriptionField = document.getElementById('description');
    if (descriptionField) {
        descriptionField.addEventListener('input', updateCharCount);
    }

    // Tải ảnh lên
    const imageInput = document.getElementById('image');
    if (imageInput) {
        imageInput.addEventListener('change', handleImageUpload);
    }
});
