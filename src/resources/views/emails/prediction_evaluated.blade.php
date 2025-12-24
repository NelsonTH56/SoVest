<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prediction Evaluated</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 24px;
        }
        .prediction-card {
            background-color: #f8f9fa;
            border-left: 4px solid {{ $accuracy >= 70 ? '#28a745' : '#dc3545' }};
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .accuracy-score {
            font-size: 48px;
            font-weight: bold;
            text-align: center;
            color: {{ $accuracy >= 90 ? '#28a745' : ($accuracy >= 70 ? '#ffc107' : '#dc3545') }};
            margin: 20px 0;
        }
        .reputation-change {
            text-align: center;
            font-size: 20px;
            margin: 15px 0;
            padding: 15px;
            background-color: {{ $reputationChange > 0 ? '#d4edda' : ($reputationChange < 0 ? '#f8d7da' : '#e2e3e5') }};
            border-radius: 4px;
            color: {{ $reputationChange > 0 ? '#155724' : ($reputationChange < 0 ? '#721c24' : '#383d41') }};
        }
        .prediction-details {
            margin: 20px 0;
        }
        .detail-row {
            margin: 10px 0;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .detail-label {
            font-weight: bold;
            color: #666;
            display: inline-block;
            width: 140px;
        }
        .detail-value {
            color: #333;
        }
        .prediction-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: bold;
            color: white;
            background-color: {{ $predictionType === 'Bullish' ? '#28a745' : '#dc3545' }};
        }
        .cta-button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
            text-align: center;
        }
        .cta-button:hover {
            background-color: #0056b3;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            color: #666;
            font-size: 14px;
        }
        .performance-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            margin: 10px 0;
        }
        .badge-excellent {
            background-color: #28a745;
            color: white;
        }
        .badge-good {
            background-color: #ffc107;
            color: #333;
        }
        .badge-poor {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Prediction Evaluated</h1>
        </div>

        <p>Hi {{ $userName }},</p>

        @if($accuracy >= 90)
            <p>Excellent news! Your prediction for <strong>{{ $stockSymbol }}</strong> was highly accurate!</p>
        @elseif($accuracy >= 70)
            <p>Good news! Your prediction for <strong>{{ $stockSymbol }}</strong> was accurate!</p>
        @else
            <p>Your prediction for <strong>{{ $stockSymbol }}</strong> has been evaluated.</p>
        @endif

        <div class="accuracy-score">
            {{ $accuracy }}%
            @if($accuracy >= 90)
                <span class="performance-badge badge-excellent">Excellent</span>
            @elseif($accuracy >= 70)
                <span class="performance-badge badge-good">Good</span>
            @else
                <span class="performance-badge badge-poor">Needs Improvement</span>
            @endif
        </div>

        <div class="prediction-card">
            <div class="prediction-details">
                <div class="detail-row">
                    <span class="detail-label">Stock:</span>
                    <span class="detail-value"><strong>{{ $stockSymbol }}</strong> - {{ $companyName }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Prediction Type:</span>
                    <span class="prediction-type">{{ $predictionType }}</span>
                </div>
                @if($targetPrice)
                <div class="detail-row">
                    <span class="detail-label">Target Price:</span>
                    <span class="detail-value">${{ number_format($targetPrice, 2) }}</span>
                </div>
                @endif
                <div class="detail-row">
                    <span class="detail-label">End Date:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($endDate)->format('F j, Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Your Reasoning:</span>
                    <span class="detail-value">{{ $reasoning }}</span>
                </div>
            </div>
        </div>

        <div class="reputation-change">
            @if($reputationChange > 0)
                🎉 You gained <strong>{{ $reputationChange }}</strong> reputation points!
            @elseif($reputationChange < 0)
                You lost <strong>{{ abs($reputationChange) }}</strong> reputation points.
            @else
                Your reputation remained unchanged.
            @endif
        </div>

        <div style="text-align: center;">
            <a href="{{ url('/predictions') }}" class="cta-button">View All Your Predictions</a>
        </div>

        <div style="margin-top: 30px; padding: 15px; background-color: #e7f3ff; border-left: 4px solid #007bff; border-radius: 4px;">
            <strong>💡 Tip:</strong>
            @if($accuracy >= 90)
                Keep up the excellent work! Your market insights are highly valuable to the community.
            @elseif($accuracy >= 70)
                You're on the right track! Continue refining your analysis for even better results.
            @else
                Use this as a learning opportunity. Analyze what factors you might have missed and refine your strategy for future predictions.
            @endif
        </div>

        <div class="footer">
            <p>Keep predicting and building your reputation!</p>
            <p style="font-size: 12px; color: #999;">
                You received this email because you made a prediction on SoVest that has reached its evaluation date.
            </p>
        </div>
    </div>
</body>
</html>
