<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PredictionEvaluated extends Mailable
{
    use Queueable, SerializesModels;

    public $prediction;
    public $user;
    public $stock;
    public $accuracy;
    public $reputationChange;

    /**
     * Create a new message instance.
     *
     * @param array $prediction Prediction data
     * @param array $user User data
     * @param array $stock Stock data
     * @param float $accuracy Prediction accuracy score
     * @param int $reputationChange Reputation points gained/lost
     */
    public function __construct($prediction, $user, $stock, $accuracy, $reputationChange)
    {
        $this->prediction = $prediction;
        $this->user = $user;
        $this->stock = $stock;
        $this->accuracy = $accuracy;
        $this->reputationChange = $reputationChange;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = $this->accuracy >= 70
            ? "Great news! Your {$this->stock['symbol']} prediction was accurate!"
            : "Your {$this->stock['symbol']} prediction has been evaluated";

        return $this->subject($subject)
                    ->view('emails.prediction_evaluated')
                    ->with([
                        'userName' => $this->user['first_name'] . ' ' . $this->user['last_name'],
                        'stockSymbol' => $this->stock['symbol'],
                        'companyName' => $this->stock['company_name'],
                        'predictionType' => $this->prediction['prediction_type'],
                        'accuracy' => number_format($this->accuracy, 2),
                        'reputationChange' => $this->reputationChange,
                        'endDate' => $this->prediction['end_date'],
                        'reasoning' => $this->prediction['reasoning'],
                        'targetPrice' => $this->prediction['target_price'] ?? null,
                    ]);
    }
}
