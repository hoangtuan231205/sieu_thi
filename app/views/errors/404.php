 <!DOCTYPE html>
            <html lang='vi'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>404 - Không tìm thấy trang</title>
                <style>
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        min-height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: #333;
                    }
                    .container {
                        text-align: center;
                        background: white;
                        padding: 60px 40px;
                        border-radius: 20px;
                        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                        max-width: 500px;
                    }
                    .error-code {
                        font-size: 120px;
                        font-weight: bold;
                        color: #667eea;
                        line-height: 1;
                        margin-bottom: 20px;
                    }
                    h1 {
                        font-size: 32px;
                        margin-bottom: 15px;
                        color: #2d3748;
                    }
                    p {
                        font-size: 18px;
                        color: #718096;
                        margin-bottom: 30px;
                        line-height: 1.6;
                    }
                    a {
                        display: inline-block;
                        background: #667eea;
                        color: white;
                        text-decoration: none;
                        padding: 15px 40px;
                        border-radius: 50px;
                        font-weight: 600;
                        transition: all 0.3s;
                    }
                    a:hover {
                        background: #5568d3;
                        transform: translateY(-2px);
                        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='error-code'>404</div>
                    <h1>Không tìm thấy trang</h1>
                    <p>Xin lỗi, trang bạn đang tìm kiếm không tồn tại hoặc đã bị di chuyển.</p>
                    <a href='" . BASE_URL . "'>Về trang chủ</a>
                </div>
            </body>
            </html>