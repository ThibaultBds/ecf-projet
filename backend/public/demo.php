<?php
$notes = [10, 15, 16];
$eleves = [
    'cm2' => ['Jean', 'Marc', 'Marion'],
    'cm1' => ['Emilie']
];

foreach ($eleves as $classe => $eleves_classe) {
    foreach ($eleves_classe as $eleve) {
        echo "$eleve est dans la classe $classe \n";
    }
}


