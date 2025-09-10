<?php
include '../config.php';
$id = $editId ?? null;
$available_day = $start_time = $end_time = "";

// If editing, fetch the schedule
if ($id) {
    $stmt = $conn->prepare("SELECT * FROM schedules WHERE id = ? AND facilitator_id = ?");
    $stmt->bind_param("ii", $id, $facilitator_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($schedule = $res->fetch_assoc()) {
        $available_day = $schedule['available_day'];
        $start_time = $schedule['start_time'];
        $end_time = $schedule['end_time'];
    }
}
?>

<!-- Bootstrap Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="schedule_save.php">
        <div class="modal-header">
          <h5 class="modal-title" id="scheduleModalLabel"><?= $id ? "Edit" : "Add" ?> Availability</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="id" value="<?= $id ?>">

          <div class="mb-3">
            <label class="form-label">Day</label>
            <select name="day" class="form-select" required>
              <?php
              $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
              foreach ($days as $d) {
                  $selected = ($d == $available_day) ? "selected" : "";
                  echo "<option value='$d' $selected>$d</option>";
              }
              ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Start Time</label>
            <input type="time" name="start_time" class="form-control" value="<?= htmlspecialchars($start_time) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">End Time</label>
            <input type="time" name="end_time" class="form-control" value="<?= htmlspecialchars($end_time) ?>" required>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary"><?= $id ? "Update" : "Save" ?></button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
