<?php
namespace App\Models;

use Core\Model;
use PDO;

class Booking extends Model {
    protected $table = 'bookings';

    public function findByUser($userId) {
        $stmt = $this->db->prepare($this->historySelect() . " WHERE b.user_id = ? ORDER BY b.created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByPhone($phone) {
        $stmt = $this->db->prepare($this->historySelect() . " WHERE b.customer_phone = ? ORDER BY b.created_at DESC");
        $stmt->execute([$phone]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function historySelect() {
        return "SELECT b.*,
                       b.booking_code AS code,
                       br.name AS branch_name,
                       c.name  AS court_name,
                       CAST(SUBSTRING(b.start_time, 1, 2) AS UNSIGNED) AS start_hour,
                       CAST(SUBSTRING(b.end_time, 1, 2)   AS UNSIGNED) AS end_hour
                FROM {$this->table} b
                LEFT JOIN branches br ON br.id = b.branch_id
                LEFT JOIN courts   c  ON c.id  = b.court_id";
    }

    public function findDetailed($id) {
        $stmt = $this->db->prepare($this->historySelect() . " WHERE b.id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByCode($code) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE booking_code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function countConfirmed() {
        $stmt = $this->db->query("SELECT COUNT(*) as cnt FROM {$this->table} WHERE status = 'confirmed'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $row['cnt'];
    }

    public function generateBookingCode() {
        $dateStr = date('Ymd');
        $randomStr = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 5));
        return "BB-{$dateStr}-{$randomStr}";
    }

    public function isSlotAvailable($courtId, $date, $startTime, $endTime) {
        $startTimeStr = sprintf('%02d:00:00', $startTime);
        $endTimeStr   = sprintf('%02d:00:00', $endTime);
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as cnt FROM {$this->table} 
            WHERE court_id = ? AND booking_date = ? AND status IN ('confirmed', 'pending')
            AND ((start_time < ? AND end_time > ?) OR (start_time >= ? AND start_time < ?))
        ");
        $stmt->execute([
            $courtId, $date, $endTimeStr, $startTimeStr, $startTimeStr, $endTimeStr
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['cnt'] == 0;
    }

    /**
     * Return the list of booked hour marks (integers) for a court on a given date.
     * Excludes expired pending bookings.
     */
    public function getBookedSlots($courtId, $date) {
        $stmt = $this->db->prepare("
            SELECT start_time, end_time FROM {$this->table}
            WHERE court_id = ? AND booking_date = ? 
            AND status IN ('confirmed', 'pending')
        ");
        $stmt->execute([$courtId, $date]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $hours = [];
        foreach ($rows as $row) {
            $start = (int) substr($row['start_time'], 0, 2);
            $end   = (int) substr($row['end_time'], 0, 2);
            for ($h = $start; $h < $end; $h++) {
                $hours[] = $h;
            }
        }
        return array_values(array_unique($hours));
    }

    public function getByBranch($branchId, $date = null) {
        $sql = "SELECT * FROM {$this->table} WHERE branch_id = ?";
        $params = [$branchId];
        if ($date) {
            $sql .= " AND booking_date = ?";
            $params[] = $date;
        }
        $sql .= " ORDER BY booking_date DESC, start_time DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll($filters = []) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['branch_id'])) {
            $sql .= " AND branch_id = ?";
            $params[] = $filters['branch_id'];
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRevenue($branchId, $period = 'month') {
        $condition = "branch_id = ? AND status = 'confirmed'";
        if ($period == 'month') {
            $condition .= " AND MONTH(booking_date) = MONTH(CURRENT_DATE()) AND YEAR(booking_date) = YEAR(CURRENT_DATE())";
        } elseif ($period == 'today') {
            $condition .= " AND booking_date = CURRENT_DATE()";
        }
        
        $stmt = $this->db->prepare("SELECT SUM(total_price) as total_revenue FROM {$this->table} WHERE {$condition}");
        $stmt->execute([$branchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_revenue'] ?: 0;
    }

    /**
     * Auto-expire confirmed bookings where end_time has passed (session timeout).
     * Slot is released. Call via cron job every 5-10 minutes.
     */
    public function autoExpireEndedSessions() {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET status = 'cancelled',
                notes = CONCAT(COALESCE(notes, ''), ' | Auto expire: hết giờ chơi')
            WHERE status IN ('confirmed', 'pending') 
            AND CONCAT(booking_date, ' ', end_time) < NOW()
        ");
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Clean up expired pending bookings (backup for missed payment page redirects).
     * Call via cron job every 1-2 minutes.
     */
    public function cleanExpiredPending() {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET status = 'cancelled',
                notes = CONCAT(COALESCE(notes, ''), ' | Auto cancel: quá hạn thanh toán')
            WHERE status = 'pending' 
            AND expires_at IS NOT NULL 
            AND expires_at < NOW()
        ");
        $stmt->execute();
        return $stmt->rowCount();
    }
}
