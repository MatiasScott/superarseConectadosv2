<?php
// app/Models/PaymentModel.php
require_once 'Database.php';
class PaymentModel extends Database
{
    protected $table_name = "payments";

    public function createPendingPayment(
        $userId,
        $clientTransactionId,
        $amount,
        $currency = 'USD',
        $status = 'PENDING'
    ) {
        $db = $this->getConnection();
        $query = "INSERT INTO " . $this->table_name . "
                    (user_id, client_transaction_id, amount, currency, status) 
                    VALUES (?, ?, ?, ?, ?)";

        $stmt = $db->prepare($query);

        try {
            $stmt->execute([$userId, $clientTransactionId, $amount, $currency, $status]);
            return $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error al crear pago pendiente: " . $e->getMessage());
            return false;
        }
    }

    public function updatePaymentStatus(
        $clientTransactionId,
        $payphoneTransactionId,
        $status
    ) {
        $db = $this->getConnection();
        $query = "UPDATE " . $this->table_name . " 
                    SET payphone_transaction_id = ?, status = ? 
                    WHERE client_transaction_id = ? AND status = 'PENDING'";

        $stmt = $db->prepare($query);

        try {
            return $stmt->execute([$payphoneTransactionId, $status, $clientTransactionId]);
        } catch (PDOException $e) {
            error_log("Error al actualizar estado de pago: " . $e->getMessage());
            return false;
        }
    }
}