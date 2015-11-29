<?php
/**
 * 2013-11-10 下午3:01:11
 * @author x.li
 * @abstract 
 */
$cron_target = 'http://localhost/evolve/public/admin/cron/run';
echo file_get_contents($cron_target);