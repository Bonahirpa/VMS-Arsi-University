<?php
// includes/footer.php - Footer
?>
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h4>About VMS</h4>
                <p>A centralized platform for managing volunteer activities at Arsi University.</p>
            </div>
            <div class="col-md-4">
                <h4>Quick Links</h4>
                <ul class="list-unstyled">
                    <li><a href="/VMS2/index.php">Home</a></li>
                    <!-- <li><a href="/VMS2/volunteer/events.php">Events</a></li> -->
                    <!-- <li><a href="/VMS2/volunteer/feedback.php">Feedback</a></li> -->
                </ul>
            </div>
            <div class="col-md-4">
                <h4>Contact</h4>
                <p><i class="fa fa-map-marker"></i> Arsi University, Asella</p>
                <p><i class="fa fa-envelope"></i> vms@arsi.edu.et</p>
            </div>
        </div>
        <hr>
        <p class="text-center">&copy; <?php echo date('Y'); ?> Arsi University - Department of Computer Science - Group 3</p>
    </div>
</footer>


<style>
.footer {
    background: #333;
    color: white;
    padding: 40px 0 20px;
    margin-top: 50px;
}
.footer a { color: #ccc; }
.footer a:hover { color: #667eea; }
</style>