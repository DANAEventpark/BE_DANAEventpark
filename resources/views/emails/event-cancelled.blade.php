<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo hủy sự kiện</title>
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
            background: linear-gradient(135deg, #ef4444, #b91c1c);
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
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            border-radius: 8px;
            padding: 20px 24px;
            margin-bottom: 24px;
        }
        .event-card h2 {
            color: #b91c1c;
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
            min-width: 120px;
            color: #374151;
        }
        .cancel-reason {
            background-color: #fff7ed;
            border: 1px solid #fdba74;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #92400e;
        }
        .cancel-reason strong {
            display: block;
            margin-bottom: 6px;
            color: #78350f;
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
            <div class="icon">❌</div>
            <h1>Sự kiện đã bị hủy</h1>
            <p>Thông báo từ DANAEventpark</p>
        </div>

        <div class="body">
            <p class="greeting">Xin chào, <strong>{{ $user->name }}</strong>!</p>

            <p class="intro">
                Chúng tôi rất tiếc phải thông báo rằng sự kiện bạn đã đăng ký tham gia đã bị <strong>hủy bỏ</strong> bởi ban tổ chức. Dưới đây là thông tin chi tiết:
            </p>

            <div class="event-card">
                <h2>{{ $event->title }}</h2>
                <div class="event-info">
                    <span class="label">📅 Ngày dự kiến:</span>
                    <span>{{ $event->start_time ? $event->start_time->format('d/m/Y H:i') : 'N/A' }}</span>
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

            @if($event->cancel_reason)
            <div class="cancel-reason">
                <strong>⚠️ Lý do hủy:</strong>
                {{ $event->cancel_reason }}
            </div>
            @endif

            <p class="footer-note">
                Chúng tôi thành thật xin lỗi vì sự bất tiện này. Hãy khám phá các sự kiện thú vị khác trên <span class="brand">DANAEventpark</span> — chúng tôi có rất nhiều hoạt động đang chờ bạn tham gia!
            </p>
        </div>

        <div class="footer">
            <p>Email này được gửi tự động từ hệ thống <span class="brand">DANAEventpark</span>. Vui lòng không trả lời email này.</p>
        </div>
    </div>
</body>
</html>
