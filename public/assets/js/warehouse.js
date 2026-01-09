// =====================================================
// STATE
// =====================================================
let addSelected = null;
let addLines = [];

let editSelected = null;
let editLines = [];
let editLineIndex = null; // index dòng đang sửa (null = add mới)


// =====================================================
// UTIL
// =====================================================
function money(n) {
  return (Number(n) || 0).toLocaleString('vi-VN') + ' đ';
}
function show(el) { el.style.display = 'flex'; }
function hide(el) { el.style.display = 'none'; }

function debounce(fn, delay = 250) {
  let t = null;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), delay);
  };
}

async function getJSON(url) {
  const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
  return r.json();
}

async function postJSON(url, data) {
  const fd = new FormData();
  Object.keys(data).forEach(k => fd.append(k, data[k]));
  const r = await fetch(url, {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  });
  return r.json();
}


// =====================================================
// MODAL OPEN/CLOSE
// =====================================================
function openAdd() {
  addLines = [];
  addSelected = null;

  document.getElementById('wh-add-date').value = '';
  document.getElementById('wh-add-note').value = '';
  document.getElementById('wh-add-q').value = '';

  clearAddPick();
  renderAddLines();
  show(document.getElementById('wh-add-modal'));
}

function closeAdd() {
  hide(document.getElementById('wh-add-modal'));
}

async function openEdit(id) {
  show(document.getElementById('wh-edit-modal'));
  await loadEdit(id);
}

function closeEdit() {
  hide(document.getElementById('wh-edit-modal'));
}

// click nền modal để đóng
document.addEventListener('click', (e) => {
  const add = document.getElementById('wh-add-modal');
  const edit = document.getElementById('wh-edit-modal');
  if (e.target === add) closeAdd();
  if (e.target === edit) closeEdit();
});


// =====================================================
// SEARCH PRODUCT (CHUNG)
// =====================================================
function bindSearch(inputId, boxId, callback) {
  const search = debounce(async () => {
    const q = document.getElementById(inputId).value.trim();
    const box = document.getElementById(boxId);

    if (q.length < 2) { box.style.display = 'none'; box.innerHTML = ''; return; }

    const json = await getJSON(`${WH_BASE}/warehouse/search-product?q=${encodeURIComponent(q)}`);
    if (!json.success || !json.products || !json.products.length) {
      box.style.display = 'none';
      box.innerHTML = '';
      return;
    }

    box.innerHTML = json.products.map(p => `
      <div class="wh-suggest-item"
        data-id="${p.id}"
        data-ma="${p.ma}"
        data-ten="${p.ten}"
        data-dvt="${p.dvt}"
        data-gia="${p.gia}">
        <div class="wh-s1">${p.ten}</div>
        <div class="wh-s2">${p.ma} • ${p.dvt} • ${money(p.gia)}</div>
      </div>
    `).join('');
    box.style.display = 'block';
  });

  document.addEventListener('input', e => {
    if (e.target && e.target.id === inputId) search();
  });

  document.addEventListener('click', e => {
    const it = e.target.closest && e.target.closest(`#${boxId} .wh-suggest-item`);
    if (!it) return;

    callback({
      ID_sp: Number(it.dataset.id),
      Ma: it.dataset.ma,
      Ten_sp: it.dataset.ten,
      Don_vi_tinh: it.dataset.dvt,
      Gia_hien_tai: Number(it.dataset.gia || 0)
    });

    const box = document.getElementById(boxId);
    box.style.display = 'none';
  });
}


// =====================================================
// ADD PHIẾU
// =====================================================
function clearAddPick() {
  document.getElementById('wh-add-ma').value = '';
  document.getElementById('wh-add-dvt').value = '';
  document.getElementById('wh-add-qty').value = 1;
  document.getElementById('wh-add-price').value = 0;
  addSelected = null;
}

bindSearch('wh-add-q', 'wh-add-suggest', p => {
  addSelected = p;
  document.getElementById('wh-add-ma').value = p.Ma;
  document.getElementById('wh-add-dvt').value = p.Don_vi_tinh;
  document.getElementById('wh-add-price').value = p.Gia_hien_tai;
});

function addLine() {
  if (!addSelected) return alert('Chưa chọn sản phẩm');

  const qty = Math.max(1, parseInt(document.getElementById('wh-add-qty').value || '1', 10));
  const price = Math.max(0, parseFloat(document.getElementById('wh-add-price').value || '0'));
  const supplierId = document.getElementById('wh-add-supplier').value;
  const supplierText = document.getElementById('wh-add-supplier').selectedOptions[0]?.text || '';
  const categoryId = document.getElementById('wh-add-category').value;
  const categoryText = document.getElementById('wh-add-category').selectedOptions[0]?.text || '';

  // Validation: Kiểm tra nhà cung cấp và danh mục
  if (!supplierId) return alert('Vui lòng chọn nhà cung cấp');
  if (!categoryId) return alert('Vui lòng chọn danh mục');

  addLines.push({
    ID_sp: addSelected.ID_sp,
    Ten_sp: addSelected.Ten_sp,
    Don_vi_tinh: addSelected.Don_vi_tinh,
    Gia_hien_tai: addSelected.Gia_hien_tai,
    So_luong: qty,
    Don_gia_nhap: price,
    ID_ncc: supplierId,
    Ten_ncc: supplierText,
    Danh_muc: categoryId,
    Ten_danh_muc: categoryText
  });

  clearAddPick();
  document.getElementById('wh-add-q').value = '';
  document.getElementById('wh-add-supplier').value = '';
  document.getElementById('wh-add-category').value = '';
  renderAddLines();
}

function renderAddLines() {
  const tb = document.getElementById('wh-add-lines');
  const total = document.getElementById('wh-add-total');

  if (!addLines.length) {
    tb.innerHTML = `<tr><td colspan="8" class="wh-empty">Chưa có sản phẩm</td></tr>`;
    total.textContent = money(0);
    return;
  }

  let sum = 0;
  tb.innerHTML = addLines.map((x, i) => {
    const t = (Number(x.So_luong) || 0) * (Number(x.Don_gia_nhap) || 0);
    sum += t;

    return `
      <tr>
        <td>${x.Ten_sp}</td>
        <td>${x.Don_vi_tinh}</td>
        <td>${x.Ten_ncc || '-'}</td>
        <td>${x.Ten_danh_muc || '-'}</td>
        <td>${x.So_luong}</td>
        <td>${money(x.Don_gia_nhap)}</td>
        <td class="wh-bold">${money(t)}</td>
        <td><button type="button" class="wh-icon wh-danger"
            onclick="addLines.splice(${i},1);renderAddLines()">🗑</button></td>
      </tr>`;
  }).join('');

  total.textContent = money(sum);
}


async function submitAdd() {
  const ngay = document.getElementById('wh-add-date').value;
  const note = (document.getElementById('wh-add-note').value || '').trim();

  if (!ngay) return alert('Chọn ngày nhập');
  if (!addLines.length) return alert('Chưa có sản phẩm');

  const json = await postJSON(`${WH_BASE}/warehouse/import-create`, {
    csrf_token: WH_CSRF || '',
    ngay_nhap: ngay,
    ghi_chu: note,
    items: JSON.stringify(addLines)
  });

  if (!json.success) return alert(json.message || 'Tạo phiếu thất bại');
  alert('Tạo phiếu nhập thành công!');
  location.reload();
}


// =====================================================
// EDIT PHIẾU
// =====================================================
function clearEditPick() {
  document.getElementById('wh-edit-add-ma').value = '';
  document.getElementById('wh-edit-add-dvt').value = '';
  document.getElementById('wh-edit-add-gia').value = '';
  document.getElementById('wh-edit-add-qty').value = 1;
  document.getElementById('wh-edit-add-price').value = 0;
  document.getElementById('wh-edit-q').value = '';
  editSelected = null;
}

bindSearch('wh-edit-q', 'wh-edit-suggest', p => {
  editSelected = p;

  document.getElementById('wh-edit-add-ma').value = p.Ma;
  document.getElementById('wh-edit-add-dvt').value = p.Don_vi_tinh;
  document.getElementById('wh-edit-add-gia').value = money(p.Gia_hien_tai);
  document.getElementById('wh-edit-add-price').value = p.Gia_hien_tai;

  // khi chọn sản phẩm mới từ search => coi như đang add mới
  editLineIndex = null;
});

async function loadEdit(id) {
  const json = await getJSON(`${WH_BASE}/warehouse/import-detail?id=${id}`);
  if (!json.success) return alert(json.message || 'Không tải được phiếu');

  const imp = json.import;

  document.getElementById('wh-edit-id').value = imp.ID_phieu_nhap;
  document.getElementById('wh-edit-code').textContent = '#' + (imp.Ma_hien_thi || '');
  document.getElementById('wh-edit-ma').value = imp.Ma_hien_thi || '';
  document.getElementById('wh-edit-date').value = (imp.Ngay_nhap || '').slice(0, 10);
  document.getElementById('wh-edit-user').value = imp.Nguoi_tao_ten || '';
  document.getElementById('wh-edit-note').value = imp.Ghi_chu || '';

  editLines = (json.items || []).map(x => ({
    ID_sp: Number(x.ID_sp),
    Ten_sp: x.Ten_sp,
    Don_vi_tinh: x.Don_vi_tinh || 'SP',
    Gia_hien_tai: Number(x.Gia_hien_tai || 0),
    So_luong: Number(x.So_luong || 1),
    Don_gia_nhap: Number(x.Don_gia_nhap || 0)
  }));

  editLineIndex = null;
  clearEditPick();
  renderEditLines();
}


function editAddLine() {
  const qty = Math.max(1, parseInt(document.getElementById('wh-edit-add-qty').value || '1', 10));
  const price = Math.max(0, parseFloat(document.getElementById('wh-edit-add-price').value || '0'));

  // nếu đang sửa dòng => chỉ update số lượng/đơn giá (và giá hiện tại nếu có)
  if (editLineIndex !== null) {
    const x = editLines[editLineIndex];
    if (!x) { editLineIndex = null; return; }

    x.So_luong = qty;
    x.Don_gia_nhap = price;

    // nếu có editSelected (chọn từ search) thì update cả giá hiện tại cho đúng
    if (editSelected && Number(editSelected.ID_sp) === Number(x.ID_sp)) {
      x.Gia_hien_tai = Number(editSelected.Gia_hien_tai || x.Gia_hien_tai || 0);
    }

    editLineIndex = null;
    clearEditPick();
    renderEditLines();
    return;
  }

  // add mới
  if (!editSelected) return alert('Chưa chọn sản phẩm');

  editLines.push({
    ID_sp: editSelected.ID_sp,
    Ten_sp: editSelected.Ten_sp,
    Don_vi_tinh: editSelected.Don_vi_tinh,
    Gia_hien_tai: editSelected.Gia_hien_tai,
    So_luong: qty,
    Don_gia_nhap: price
  });

  clearEditPick();
  renderEditLines();
}

// ✅ NÚT ✏️: đổ dữ liệu lên form để sửa
function pickEditLine(i) {
  const x = editLines[i];
  if (!x) return;

  editLineIndex = i;
  editSelected = { ID_sp: x.ID_sp, Ten_sp: x.Ten_sp, Don_vi_tinh: x.Don_vi_tinh, Gia_hien_tai: x.Gia_hien_tai };


  document.getElementById('wh-edit-add-ma').value = x.ID_sp;
  document.getElementById('wh-edit-add-dvt').value = x.Don_vi_tinh;
  document.getElementById('wh-edit-add-gia').value = money(x.Gia_hien_tai || 0);
  document.getElementById('wh-edit-add-qty').value = x.So_luong;
  document.getElementById('wh-edit-add-price').value = x.Don_gia_nhap;

  document.getElementById('wh-edit-add-qty').focus();
}

function renderEditLines() {
  const tb = document.getElementById('wh-edit-lines');
  const total = document.getElementById('wh-edit-total');

  if (!editLines.length) {
    tb.innerHTML = `<tr><td colspan="7" class="wh-empty">Chưa có sản phẩm</td></tr>`;
    total.textContent = money(0);
    return;
  }

  let sum = 0;
  tb.innerHTML = editLines.map((x, i) => {
    const t = (Number(x.So_luong) || 0) * (Number(x.Don_gia_nhap) || 0);
    sum += t;

    return `
      <tr>
        <td>${x.Ten_sp}</td>
        <td>${x.Don_vi_tinh}</td>
        <td>${money(x.Gia_hien_tai || 0)}</td>
        <td>${x.So_luong}</td>
        <td>${money(x.Don_gia_nhap)}</td>
        <td class="wh-bold">${money(t)}</td>
        <td>
          <button type="button" class="wh-icon wh-edit" title="Sửa" onclick="pickEditLine(${i})">✏️</button>
          <button type="button" class="wh-icon wh-danger" title="Xóa"
              onclick="if(editLineIndex===${i}) editLineIndex=null; editLines.splice(${i},1); renderEditLines()">🗑</button>
        </td>
      </tr>`;
  }).join('');

  total.textContent = money(sum);
}

// =====================================================
// SUBMIT EDIT
// =====================================================
async function submitEdit() {
  const id = document.getElementById('wh-edit-id').value;
  const ngay = document.getElementById('wh-edit-date').value;
  const note = (document.getElementById('wh-edit-note').value || '').trim();

  if (!id) return alert('Thiếu ID phiếu');
  if (!ngay) return alert('Chọn ngày nhập');
  if (!editLines.length) return alert('Chưa có sản phẩm');

  const json = await postJSON(`${WH_BASE}/warehouse/import-update`, {
    csrf_token: WH_CSRF || '',
    id_phieu_nhap: id,
    ngay_nhap: ngay,
    ghi_chu: note,
    items: JSON.stringify(editLines)
  });

  if (!json.success) return alert(json.message || 'Lỗi cập nhật');
  alert('Đã cập nhật');
  location.reload();
}


// =====================================================
// DELETE IMPORT
// =====================================================
function deleteImport(id) {
  if (!confirm('Bạn có chắc muốn xóa phiếu nhập này?')) return;

  fetch(`${WH_BASE}/warehouse/import-delete`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: 'id=' + encodeURIComponent(id)
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        alert('Đã xóa phiếu nhập');
        location.reload();
      } else {
        alert(res.message || 'Xóa thất bại');
      }
    });
}
document.addEventListener('DOMContentLoaded', () => {
  const resetBtn = document.getElementById('wh-reset-search');
  const form = document.getElementById('wh-search-form');

  if (!resetBtn || !form) return;

  resetBtn.addEventListener('click', () => {
    // reset input UI
    form.reset();

    // xoá query trên URL, quay về dashboard sạch
    window.location.href = form.action;
  });
});
