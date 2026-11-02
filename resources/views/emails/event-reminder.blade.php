<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhắc nhở sự kiện ngày mai</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f4f7;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .wrapper {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .header {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            padding: 36px 40px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 24px;
            margin: 0 0 8px 0;
            font-weight: 700;
        }
        .header p {
            color: rgba(255,255,255,0.85);
            margin: 0;
            font-size: 14px;
        }
        .icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
        .body {
            padding: 36px 40px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 16px;
            color: #374151;
        }
        .intro {
            font-size: 15px;
            line-height: 1.7;
            color: #4B5563;
            margin-bottom: 24px;
        }
        .event-card {
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            border-left: 4px solid #6366f1;
            border-radius: 8px;
            padding: 20px 24px;
            margin-bottom: 24px;
        }
        .event-card h2 {
            color: #4338ca;
            font-size: 18px;
            margin: 0 0 14px 0;
        }
        .event-info {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
            font-size: 14px;
            color: #4B5563;
        }
        .event-info .label {
            font-weight: 600;
            min-width: 130px;
            color: #374151;
        }
        .countdown-badge {
            display: inline-block;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            font-size: 13px;
            font-weight: 700;
            padding: 8px 20px;
            border-radius: 20px;
            margin-bottom: 24px;
        }
        .cta-note {
            background-color: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #166534;
        }
        .footer-note {
            font-size: 14px;
            color: #6B7280;
            line-height: 1.6;
            margin-bottom: 0;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px 40px;
            text-align: center;
            border-top: 1px solid #E5E7EB;
        }
        .footer p {
            margin: 0;
            font-size: 12px;
            color: #9CA3AF;
        }
        .brand {
            font-weight: 700;
            color: #6366f1;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="icon">🔔</div>
            <h1>Nhắc nhở sự kiện</h1>
            <p>Sự kiện của bạn sẽ diễn ra vào ngày mai!</p>
        </div>

        <div class="body">
            <p class="greeting">Xin chào, <strong>{{ $user->name }}</strong>!</p>

            <p class="intro">
                Chỉ còn <strong>1 ngày nữa</strong> là sự kiện bạn đã đăng ký sẽ diễn ra. Hãy chuẩn bị để có một trải nghiệm tuyệt vời!
            </p>

            <div style="text-align:center; margin-bottom: 20px;">
                <span class="countdown-badge">⏰ Còn 1 ngày nữa!</span>
            </div>

            <div class="event-card">
                <h2>{{ $event->title }}</h2>
                <div class="event-info">
                    <span class="label">📅 Thời gian bắt đầu:</span>
                    <span>{{ $event->start_time ? $event->start_time->format('d/m/Y H:i') : 'N/A' }}</span>
                </div>
                <div class="event-info">
                    <span class="label">🕐 Thời gian kết thúc:</span>
                    <span>{{ $event->end_time ? $event->end_time->format('d/m/Y H:i') : 'N/A' }}</span>
                </div>
                <div class="event-info">
                    <span class="label">📍 Địa điểm:</span>
                    <span>{{ $event->location }}</span>
                </div>
                <div class="event-info">
                    <span class="label">🏷️ Danh mục:</span>
                    <span>{{ $event->category ? $event->category->name : 'N/A' }}</span>
                </div>
            </div>

            <div class="cta-note">
                ✅ <strong>Bạn đã được xác nhận tham gia sự kiện này.</strong> Hãy đến đúng giờ và mang theo thông tin đăng ký của bạn.
            </div>

            <p class="footer-note">
                Hẹn gặp bạn tại sự kiện! Nếu bạn không thể tham dự, hãy hủy đăng ký trước thời hạn trên <span class="brand">DANAEventpark</span> để nhường chỗ cho những người khác.
            </p>
        </div>

        <div class="footer">
            <p>Email này được gửi tự động từ hệ thống <span class="brand">DANAEventpark</span>. Vui lòng không trả lời email này.</p>
        </div>
    </div>
</body>
</html>
