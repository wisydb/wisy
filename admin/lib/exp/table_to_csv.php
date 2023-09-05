<?php

  if (isset($_POST['csv_data'])) {
    $csv_data = $_POST['csv_data'];
    $filename = 'table_data.csv';
    
    $csv_data = str_replace( array('↗', '|||'), '', $csv_data);
    
    // Send appropriate headers to the browser
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Output the CSV data
    echo $csv_data;
    exit;
  }
  
?>