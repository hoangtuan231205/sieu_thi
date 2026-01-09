<div class="wh-modal" id="wh-add-modal">
  <div class="wh-modal-box wh-modal-xl">
    <div class="wh-modal-head">
      <div>
        <div class="wh-modal-title">Tạo Phiếu Nhập Kho Mới</div>
        <div class="wh-modal-sub">Nhập kho › Tạo phiếu nhập</div>
      </div>
      <button class="wh-close" type="button" onclick="closeAdd()">×</button>
    </div>

    <div class="wh-modal-body" style="display: grid; grid-template-columns: 1fr 2fr; gap: 16px;">

      <div class="wh-panel">
        <h3 class="wh-panel-title">Thông Tin Phiếu</h3>

        <div class="wh-field" style="display:none;">
          <label>Mã phiếu</label>
          <input disabled placeholder="Tự động sinh (trigger)">
        </div>

        <div class="wh-field">
          <label>Ngày nhập</label>
          <input type="date" id="wh-add-date">
        </div>

        <div class="wh-field">
          <label>Ghi chú</label>
          <textarea id="wh-add-note" placeholder="Nhập ghi chú chung..."></textarea>
        </div>

        <div class="wh-total">
          Tổng tiền: <b id="wh-add-total">0 đ</b>
        </div>
      </div>

      <div class="wh-panel">
        <h3 class="wh-panel-title">Thêm Chi Tiết Sản Phẩm</h3>

        <div class="wh-field wh-searchbox">
          <label>Tìm sản phẩm (tên hoặc mã)</label>
          <input id="wh-add-q" placeholder="Nhập >= 2 ký tự...">
          <div class="wh-suggest" id="wh-add-suggest"></div>
        </div>

        <div class="wh-row-2">
          <div class="wh-field">
            <label>Mã SP</label>
            <input id="wh-add-ma" disabled>
          </div>
          <div class="wh-field">
            <label>ĐVT</label>
            <input id="wh-add-dvt" disabled>
          </div>
        </div>

        <div class="wh-row-2">
          <div class="wh-field">
            <label>Nhà cung cấp</label>
            <select id="wh-add-supplier">
              <option value="">-- Chọn nhà cung cấp --</option>
              <?php
              $suppliers = $suppliers ?? [];
              foreach ($suppliers as $supplier):
                ?>
                <option value="<?= htmlspecialchars($supplier['ID_ncc']) ?>">
                  <?= htmlspecialchars($supplier['Ten_ncc']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="wh-field">
            <label>Danh mục</label>
            <select id="wh-add-category">
              <option value="">-- Chọn danh mục --</option>
              <option value="thuc-pham">Thực phẩm</option>
              <option value="do-uong">Đồ uống</option>
              <option value="do-gia-dung">Đồ gia dụng</option>
              <option value="my-pham">Mỹ phẩm</option>
              <option value="dien-tu">Điện tử</option>
              <option value="thoi-trang">Thời trang</option>
              <option value="khac">Khác</option>
            </select>
          </div>
        </div>

        <div class="wh-row-2">
          <div class="wh-field">
            <label>Số lượng nhập</label>
            <input id="wh-add-qty" type="number" min="1" value="1">
          </div>
          <div class="wh-field">
            <label>Đơn giá nhập</label>
            <input id="wh-add-price" type="number" min="0" value="0">
          </div>
        </div>

        <button class="wh-btn wh-btn-success" type="button" onclick="addLine()">+ Thêm vào danh sách</button>
      </div>
    </div>

    <div class="wh-panel">
      <h3 class="wh-panel-title">Danh Sách Hàng Hóa</h3>

      <table class="wh-table">
        <thead>
          <tr>
            <th>SẢN PHẨM</th>
            <th>ĐVT</th>
            <th>NHÀ CUNG CẤP</th>
            <th>DANH MỤC</th>
            <th>SỐ LƯỢNG</th>
            <th>GIÁ NHẬP</th>
            <th>THÀNH TIỀN</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="wh-add-lines">
          <tr>
            <td colspan="8" class="wh-empty">Chưa có sản phẩm</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="wh-modal-foot">
      <button class="wh-btn wh-btn-outline" type="button" onclick="closeAdd()">Hủy</button>
      <button class="wh-btn wh-btn-primary" type="button" onclick="submitAdd()">💾 Lưu Phiếu</button>
    </div>
  </div>
</div>