    <?php
    /**
     * =============================================================================
     * TRANG CHỦ - HOME PAGE
     * File: app/views/customer/home.php
     * =============================================================================
     */

    // Set page title
    $data['page_title'] = $data['page_title'] ?? 'FreshMart - Siêu thị thực phẩm tươi sống';

    // Include header
    include __DIR__ . '/../layouts/header.php';
    ?>

    <!-- ============================================================================
        HERO SLIDER - Banner chính (Boxed)
        ============================================================================ -->
    <section class="hero-slider">
        <div class="container py-3">
            <div class="slider-container">
                
                <!-- Slide 1 -->
                <div class="hero-slide-full active hero-slide-variant-1" style="background-image: url('<?= asset('img/hero/vegetables.png') ?>');">
                    <div class="container text-start">
                        <div class="hero-content">
                            <span class="hero-badge">🌿 Fresh & Organic</span>
                            <h1 class="hero-title">
                                Rau củ tươi ngon
                                <span class="highlight">mỗi ngày</span>
                            </h1>
                            <p class="hero-desc">
                                Thực phẩm tươi sống, chất lượng cao từ các nông trại uy tín
                            </p>
                            <a href="<?= BASE_URL ?>/products?category=5" class="btn btn-woodland btn-lg">
                                Mua ngay
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Slide 2 -->
                <div class="hero-slide-full hero-slide-variant-2" style="background-image: url('<?= asset('img/hero/milk.png') ?>');">
                    <div class="container text-start">
                        <div class="hero-content">
                            <span class="hero-badge">🥛 Fresh Dairy</span>
                            <h1 class="hero-title">
                                Sữa tươi nguyên chất
                                <span class="highlight">100%</span>
                            </h1>
                            <p class="hero-desc">
                                Sữa tươi sạch từ các trang trại đạt chuẩn quốc tế
                            </p>
                            <a href="<?= BASE_URL ?>/products?category=1" class="btn btn-woodland btn-lg">
                                Khám phá ngay
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Slide 3 -->
                <div class="hero-slide-full hero-slide-variant-3" style="background-image: url('<?= asset('img/hero/meat.png') ?>');">
                    <div class="container text-start">
                        <div class="hero-content">
                            <span class="hero-badge">🍖 Premium Quality</span>
                            <h1 class="hero-title">
                                Thịt & Hải sản
                                <span class="highlight">tươi sống</span>
                            </h1>
                            <p class="hero-desc">
                                Nguồn gốc rõ ràng, đảm bảo vệ sinh an toàn thực phẩm
                            </p>
                            <a href="<?= BASE_URL ?>/products?category=17" class="btn btn-woodland btn-lg">
                                Xem sản phẩm
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Navigation Arrows -->
                <button class="slider-nav prev" onclick="prevSlide()">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="slider-nav next" onclick="nextSlide()">
                    <i class="fas fa-chevron-right"></i>
                </button>
                
                <!-- Dots Navigation -->
                <div class="slider-dots">
                    <span class="dot active" onclick="goToSlide(0)"></span>
                    <span class="dot" onclick="goToSlide(1)"></span>
                    <span class="dot" onclick="goToSlide(2)"></span>
                </div>
                
            </div>
        </div>
    </section>

    <!-- ============================================================================
        FEATURES - Đặc điểm nổi bật (Horizontal Style)
        ============================================================================ -->
    <section class="features-section">
        <div class="container">
            <div class="row g-4">
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="feature-content">
                            <h5 class="feature-title">Chất lượng 100%</h5>
                            <p class="feature-desc">Chứng nhận thực phẩm sạch chuẩn quốc tế.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="feature-content">
                            <h5 class="feature-title">Giao Siêu Tốc</h5>
                            <p class="feature-desc">Nhận hàng tươi ngon chỉ trong 2 giờ.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="feature-content">
                            <h5 class="feature-title">Đổi trả dễ dàng</h5>
                            <p class="feature-desc">Hoàn tiền nếu không hài lòng chất lượng.</p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </section>

    <!-- ============================================================================
        BEST SELLERS - Sản phẩm bán chạy
        ============================================================================ -->
    <section class="products-section">
        <div class="container">
            
            <!-- Section Header - Tailwind Style with Green Bar -->
            <div class="section-header">
                <div class="section-header-left">
                    <div class="section-header-accent"></div>
                    <h2 class="section-title">Sản phẩm bán chạy</h2>
                </div>
                <a href="<?= BASE_URL ?>/products" class="view-all-link">
                    Xem tất cả <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <!-- Products Grid - 4 Columns (Tailwind Style) -->
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
                
                <?php if (!empty($best_sellers)): ?>
                    <?php foreach ($best_sellers as $product): ?>
                    <div class="col">
                        <!-- Standardized Product Card -->
                        <div class="product-card-standard">
                            <!-- Product Image -->
                            <div class="product-image-wrapper">
                                <?php 
                                $imagePath = getProductImagePath($product['Hinh_anh'] ?? '');
                                if (!empty($imagePath)): 
                                ?>
                                    <a href="<?= BASE_URL ?>/products/detail/<?= $product['ID_sp'] ?>">
                                        <img src="<?= asset('img/products/' . $imagePath) ?>" 
                                             alt="<?= htmlspecialchars($product['Ten']) ?>">
                                    </a>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>/products/detail/<?= $product['ID_sp'] ?>">
                                        <img src="<?= asset('img/placeholder-product.png') ?>" 
                                             alt="<?= htmlspecialchars($product['Ten']) ?>">
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Product Info -->
                            <div class="product-info">                          
                                <a href="<?= BASE_URL ?>/products/detail/<?= $product['ID_sp'] ?>" style="text-decoration: none;">
                                    <h3 class="product-name"><?= htmlspecialchars($product['Ten']) ?></h3>
                                </a>
                                
                                <!-- Price -->
                                <div class="product-price">
                                    <?= number_format($product['Gia_tien'], 0, ',', '.') ?>₫
                                </div>
                            </div>
                            
                            <!-- Action Buttons - 2 columns -->
                            <div class="product-actions">
                                <?php if ($product['So_luong_ton'] > 0): ?>
                                    <button class="btn-add" onclick="addToCart(<?= $product['ID_sp'] ?>, 1)">
                                        <i class="fas fa-cart-plus"></i> Thêm
                                    </button>
                                    <button class="btn-buy" onclick="buyNow(<?= $product['ID_sp'] ?>, 1)">
                                        Mua ngay
                                    </button>
                                <?php else: ?>
                                    <button class="btn-add" style="grid-column: span 2; background: #9ca3af; cursor: not-allowed;" disabled>
                                        <i class="fas fa-ban"></i> Hết hàng
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p class="text-center text-muted">Không có sản phẩm bán chạy</p>
                    </div>
                <?php endif; ?>
                
            </div>
            
        </div>
    </section>

    <!-- ============================================================================
        CATEGORIES BANNER - Banner danh mục
        ============================================================================ -->
    <section class="categories-banner">
        <div class="container">
            <div class="row g-4">
                
                <div class="col-lg-4 col-md-6">
                    <div class="category-banner category-banner-variant-1">
                        <div class="banner-content">
                            <h3>Rau củ quả</h3>
                            <p>Tươi mỗi ngày</p>
                            <a href="<?= BASE_URL ?>/products?category=5" class="banner-link">
                                Mua ngay <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                        <div class="banner-image">
                            <img src="<?= asset('img/categories/vegetables.png') ?>" alt="Rau củ">
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="category-banner category-banner-variant-2">
                        <div class="banner-content">
                            <h3>Thịt & Hải sản</h3>
                            <p>Tươi sống hàng ngày</p>
                            <a href="<?= BASE_URL ?>/products?category=17" class="banner-link">
                                Mua ngay <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                        <div class="banner-image">
                            <img src="<?= asset('img/categories/meat.png') ?>" alt="Thịt">
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="category-banner category-banner-variant-3">
                        <div class="banner-content">
                            <h3>Sữa & Thực phẩm</h3>
                            <p>Chất lượng cao</p>
                            <a href="<?= BASE_URL ?>/products?category=1" class="banner-link">
                                Mua ngay <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                        <div class="banner-image">
                            <img src="<?= asset('img/categories/dairy.png') ?>" alt="Sữa">
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </section>

    <?php
    // Include footer
    include __DIR__ . '/../layouts/footer.php';
    ?>