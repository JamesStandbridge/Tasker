<?php

$member_file = file_get_contents(__DIR__."/data/members.json");
$task_file = file_get_contents(__DIR__."/data/tasks.json");
$members = json_decode($member_file, true);
$tasks = json_decode($task_file, true);

var_dump($tasks);
//var_dump($members);
