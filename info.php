<?php
@mysqli_connect("127.0.0.1", "lla", "paxxw0rd@2791", "edusms", 3306);
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
} else {
    echo "DB Connected";
}
// phpinfo();
