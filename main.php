<?php

/**
 * ----------------------------------------------
 * --------------IMPORT DES FICHIERS-------------
 * ----------------------------------------------
 */
$member_file = file_get_contents(__DIR__."/data/members.json");
$task_file = file_get_contents(__DIR__."/data/tasks.json");
$members = json_decode($member_file, true)["members"];
$tasks = json_decode($task_file, true)["tasks"];


/**
 * ----------------------------------------------
 * ------------------INIT VAR--------------------
 * ----------------------------------------------
 */

$total_assignment = 0;
$total_difficulty = 0;
foreach($tasks as $task) {
	$total_assignment += $task['number_needed'];
	$total_difficulty += $task['difficulty'];
}

$count_members = count($members);
$count_tasks = count($tasks);

$average_difficulty = $total_difficulty / $count_tasks;
$average_task_by_member = $total_assignment / $count_members;

$average_load_by_member = $average_difficulty * $average_task_by_member;


//var_dump($average_load_by_member);
//var_dump($members);





/**
 * ----------------------------------------------
 * -------------------SCRIPT---------------------
 * ----------------------------------------------
 */

$list_assignements = array();

/**
 * METHOD 1
 */

// foreach($members as $member) {
// 	$assignement = array("member" => $member, "tasks" => [], "load" => 0);
// 	$turns = 0;
// 	while($assignement["difficulty"] < $average_load_by_member && count($assignement["tasks"]) < $average_task_by_member - 1) {
// 		$turns += 1;
// 		$index = array_rand($tasks);

// 		if($turns > 100 || isSlotOpen($assignement["tasks"], $tasks[$index]["slot"])) {
// 			$assignement["load"] += $tasks[$index]["difficulty"];
// 			$assignement["tasks"][] = $tasks[$index];

// 			$tasks[$index]["number_needed"] -= 1;
// 			if($tasks[$index]["number_needed"] === 0) {
// 				array_splice($tasks, $index, 1);
// 			}
// 		}
// 	}

// 	$list_assignements[] = $assignement;
// }

// var_dump($list_assignements);


/**
 * METHOD 2
 */

foreach($members as $member) {
	$assignement = array("member" => $member, "tasks" => [], "load" => 0);
	$list_assignements[] = $assignement;
}

$unassigned_tasks = [];

foreach($tasks as $key => $task) {
	for($i = 1; $i <= $task["number_needed"]; $i++) {
		$list_members = filterMembersBySlot($list_assignements, $task["slot"]);
		$list_members = filterMembersByLoad($list_members, $task["difficulty"], getCurrentLoadAverage($list_assignements), $average_load_by_member);

		$list_members = filterMembersByMinCountTask($list_members);

		if(count($list_members) > 0) {
			$index = findIndexByName($list_assignements, $list_members[array_rand($list_members)]["member"]);

			$list_assignements[$index]["tasks"][] = $task;
			$list_assignements[$index]["load"] += $task["difficulty"];
		} else {
			$unassigned_tasks[] = $task;
		}		
	}
}



/**
 * ------------------------------------------------
 * -----------------2ND MIX------------------------
 * ------------------------------------------------
 */

print_r($list_assignements);

foreach($unassigned_tasks as $task) {
	$list_members = filterMembersBySlot($list_assignements, $task["slot"]);
	//$list_members = filterMembersByLowestCombo($list_members);

	$index = findIndexByName($list_assignements, $list_members[array_rand($list_members)]["member"]);	
	$list_assignements[$index]["tasks"][] = $task;
	$list_assignements[$index]["load"] += $task["difficulty"];
}

//print_r($unassigned_tasks);
print_r($list_assignements);

/**
 * ------------------------------------------------
 * --------------------KPIs------------------------
 * ------------------------------------------------
 */
$tasks_assigned = 0;
foreach($list_assignements as $member) {
	$tasks_assigned += count($member["tasks"]);
}

print_r("count tasks assigned : " . $tasks_assigned . PHP_EOL);
print_r("count total tasks to assign : " . $total_assignment . PHP_EOL);

print_r("average task by member : " . $average_task_by_member . PHP_EOL);
print_r("current average task by member : " . getCurrentCountTaskAverage($list_assignements) . PHP_EOL);



/**
 * ----------------------------------------------
 * -----------------FUNCTIONS--------------------
 * ----------------------------------------------
 */

function getCurrentCountTaskAverage(array $members)
{
	$total = 0;
	foreach($members as $member) {
		$total += count($member["tasks"]);
	}
	return $total / count($members);
}

function getCurrentLoadAverage(array $members) 
{
	$total = 0;
	foreach($members as $member) {
		$total += $member["load"];
	}
	return $total / count($members);
}

function findIndexByName(array $members, string $name) 
{
	foreach($members as $index => $member) {
		if($member["member"] === $name) return $index;
	}
	return null;
}

function filterMembersByLowestCombo(array $members) 
{
	$minCombo = array("minLoad" => 0, "minCountTasks" => 0);
	foreach($members as $index => $member) {
		if($index === 0) {
			$minCount = array("minLoad" => $member["load"], "minCountTasks" => count($member["tasks"]));
		} else {

			if($minCombo["minLoad"] >= $member["load"] && $minCombo["minCountTasks"] >= count($member["tasks"])) {
				$minCount = array("minLoad" => $member["load"], "minCountTasks" => count($member["tasks"]));
			}
		}
	}	

	$newList = [];
	foreach($members as $member) {
		if($member["load"] === $minCombo["minLoad"] && count($member["tasks"]) === $minCombo["minCountTasks"]) {
			$newList[] = $member;
		}
	}

	return $newList;
}

function filterMembersByMinCountTask(array $members)
{
	$minCount = 0;
	foreach($members as $index => $member) {
		if($index === 0) {
			$minCount = count($member["tasks"]);
		} else {
			$minCount = min(count($member["tasks"]), $minCount);
		}
	}	

	$newList = [];
	foreach($members as $member) {
		if(count($member["tasks"]) === $minCount) $newList[] = $member;
	}

	return $newList;
}

function filterMembersByLoad(array $members, int $loadToAdd, $current_average_load_by_member, $average_load_by_member)
{
	$newList = [];
	foreach($members as $member) {
		if($loadToAdd > $current_average_load_by_member) {
			if($member["load"] + $loadToAdd <= $average_load_by_member) {
				$newList[] = $member;
			}
		} else {
			if($member["load"] < $current_average_load_by_member) {
				$newList[] = $member;
			}
		}
	}
	return $newList;
} 

function filterMembersBySlot(array $members, string $slot)
{
	$newList = [];
	foreach($members as $member) {
		$bool = true;
		foreach($member["tasks"] as $task) {
			if($task["slot"] === $slot) $bool = false;
		}
		if($bool) $newList[] = $member;
	}
	return $newList;
}

function isSlotOpen(array $assigned_tasks, string $slot)
{
	foreach($assigned_tasks as $task) {
		if($task["slot"] === $slot) return false;
	}
	return true;
}

function hasEndOfStayTask(array $assigned_tasks)
{
	foreach($assigned_tasks as $task) {
		if($task["isEndOfStay"]) return true;
	}
	return false;
}





























// {"label": "Responsable chambres", "number_needed": 1, "difficulty": 6, "isAccountable": true},
// {"label": "Responsable jardin", "number_needed": 1, "difficulty": 5, "isAccountable": true},
// {"label": "Responsable cuisine", "number_needed": 1, "difficulty": 6, "isAccountable": true},
// {"label": "Responsable salle a manger", "number_needed": 1, "difficulty": 6, "isAccountable": true},
// {"label": "Responsable salons (autres salles quoi)", "number_needed": 1, "difficulty": 5, "isAccountable": true},
// {"label": "Responsable salle de bain / WC", "number_needed": 1, "difficulty": 7, "isAccountable": true},