<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Teacher ID Card</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .id-card {
            width: 350px;
            height: 220px;
            border: 2px solid #333;
            border-radius: 10px;
            padding: 15px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            position: relative;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        .school-header {
            text-align: center;
            margin-bottom: 15px;
        }
        .school-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .card-title {
            font-size: 14px;
            background: rgba(255,255,255,0.2);
            padding: 3px 10px;
            border-radius: 15px;
            display: inline-block;
        }
        .content {
            display: flex;
            gap: 15px;
        }
        .photo {
            width: 80px;
            height: 100px;
            background: #fff;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            font-size: 12px;
        }
        .details {
            flex: 1;
        }
        .detail-row {
            margin-bottom: 8px;
            font-size: 12px;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 70px;
        }
        .qr-code {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 3px;
        }
        .card-number {
            position: absolute;
            bottom: 5px;
            left: 15px;
            font-size: 10px;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="id-card">
        <div class="school-header">
            <div class="school-name">{{ $school->name }}</div>
            <div class="card-title">TEACHER ID CARD</div>
        </div>
        
        <div class="content">
            <div class="photo">
                @if(isset($idCard) && $idCard->photo_url)
                    <img src="{{ $idCard->photo_url }}" alt="Photo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 5px;">
                @else
                    PHOTO
                @endif
            </div>
            
            <div class="details">
                <div class="detail-row">
                    <span class="label">Name:</span>
                    <span>{{ $teacher->user->full_name }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Emp ID:</span>
                    <span>{{ $teacher->employee_id }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Qual:</span>
                    <span>{{ $teacher->qualification ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Phone:</span>
                    <span>{{ $teacher->phone ?? $teacher->user->phone }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Joined:</span>
                    <span>{{ $teacher->joining_date->format('M Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Valid:</span>
                    <span>{{ isset($idCard) ? $idCard->expiry_date->format('M Y') : date('M Y', strtotime('+1 year')) }}</span>
                </div>
            </div>
        </div>
        
        @if(isset($idCard) && $idCard->qr_code_url)
            <div class="qr-code">
                <img src="{{ $idCard->qr_code_url }}" alt="QR Code" style="width: 100%; height: 100%;">
            </div>
        @endif
        
        <div class="card-number">{{ isset($idCard) ? $idCard->card_number : 'CARD-T' . $teacher->id }}</div>
    </div>
</body>
</html>