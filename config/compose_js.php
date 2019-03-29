<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
    return $config = array( 
        'internal_js' => array('compose', ),
        'external_js' => array(
            '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@8.2.4/dist/sweetalert2.all.min.js" integrity="sha256-G83CHUL43nu8OZ2zyBVK4hXi1JydCwBZPabp7ufO7Cc=" crossorigin="anonymous"></script>'
        ,
            '<script src="https://unpkg.com/papaparse@4.6.3/papaparse.min.js"></script>',
            '<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>'
        ),
    );
//EOF