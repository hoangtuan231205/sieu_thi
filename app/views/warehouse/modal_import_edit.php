<div class="wh-modal" id="wh-edit-modal">
  <div class="wh-modal-box wh-modal-xl">
    <div class="wh-modal-head">
      <div>
        <div class="wh-modal-title">Chi Tiết Phiếu Nhập <span id="wh-edit-code"></span></div>
        <div class="wh-modal-sub">Nhập kho › Chỉnh sửa</div>
      </div>
      <button class="wh-close" type="button" onclick="closeEdit()">×</button>
    </div>

    <input type="hidden" id="wh-edit-id">

    <div class="wh-modal-body">
      <div class="wh-panel wh-grid-3">
        <div class="wh-field">
          <label>Mã hiển thị</label>
          <input id="wh-edit-ma" disabled>
        </div>
        <div class="wh-field">
          <label>Ngày nhập</label>
          <input type="date" id="wh-edit-date">
        </div>
        <div class="wh-field">
          <label>Người tạo</label>
          <input id="wh-edit-user" disabled>
        </div>

        <div class="wh-field wh-grid-span-3">
          <label>Ghi chú</label>
          <textarea id="wh-edit-note"></textarea>
        </div>
      </div>

      <!-- Search product to add -->
      <div class="wh-panel">
        <h3 class="wh-panel-title">Thêm sản phẩm vào phiếu</h3>

        <div class="wh-field wh-searchbox">
          <label>Tìm sản phẩm (tên hoặc mã)</label>
          <input id="wh-edit-q" placeholder="Nhập >= 2 ký tự...">
          <div class="wh-suggest" id="wh-edit-suggest"></div>
        </div>

        <div class="wh-row-3">
          <div class="wh-field">
            <label>Mã SP</label>
            <input id="wh-edit-add-ma" disabled>
          </div>
          <div class="wh-field">
            <label>ĐVT</label>
            <input id="wh-edit-add-dvt" disabled>
          </div>
          <div class="wh-field">
            <label>Giá hiện tại</label>
            <input id="wh-edit-add-gia" disabled>
          </div>
        </div>

        <div class="wh-row-2">
          <div class="wh-field">
            <label>Số lượng</label>
            <input id="wh-edit-add-qty" type="number" min="1" value="1">
          </div>
          <div class="wh-field">
            <label>Đơn giá nhập</label>
            <input id="wh-edit-add-price" type="number" min="0" value="0">
          </div>
        </div>

        <button class="wh-btn wh-btn-success" type="button" onclick="editAddLine()">+ Thêm vào danh sách</button>
      </div>

      <div class="wh-panel">
        <h3 class="wh-panel-title">Chi tiết sản phẩm</h3>

        <table class="wh-table">
          <thead>
            <tr>
              <th>SẢN PHẨM</th>
              <th>ĐVT</th>
              <th>GIÁ HIỆN TẠI</th>
              <th>SỐ LƯỢNG</th>
              <th>ĐƠN GIÁ</th>
              <th>HẠN SỬ DỤNG</th>
              <th>THÀNH TIỀN</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="wh-edit-lines"></tbody>
        </table>

        <div class="wh-total wh-total-right">
          Tổng tiền: <b id="wh-edit-total">0 đ</b>
        </div>
      </div>
    </div>

    <div class="wh-modal-foot">
      <button class="wh-btn wh-btn-outline" type="button" onclick="closeEdit()">Hủy</button>
      <button class="wh-btn wh-btn-primary" type="button" onclick="submitEdit()">💾 Lưu Thay Đổi</button>
    </div>
  </div>
</div>
