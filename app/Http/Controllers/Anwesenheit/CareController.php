<?php

namespace App\Http\Controllers\Anwesenheit;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class CareController extends Controller
{
    public function index()
    {
        $groups = ['Frühling', 'Sommer', 'Herbst', 'Winter'];
        $classes = ['Klasse 1', 'Klasse 2', 'Klasse 3', 'Klasse 4'];

        $students = Cache::remember('childs', 600, function ()  use ($groups, $classes) {

            $students = collect();

            $groupClassCount = [
                'Frühling' => ['Klasse 1' => 0, 'Klasse 2' => 0, 'Klasse 3' => 0, 'Klasse 4' => 0],
                'Sommer' => ['Klasse 1' => 0, 'Klasse 2' => 0, 'Klasse 3' => 0, 'Klasse 4' => 0],
                'Herbst' => ['Klasse 1' => 0, 'Klasse 2' => 0, 'Klasse 3' => 0, 'Klasse 4' => 0],
                'Winter' => ['Klasse 1' => 0, 'Klasse 2' => 0, 'Klasse 3' => 0, 'Klasse 4' => 0],
            ];

            for ($i = 1; $i <= 100; $i++) {
                $group = $groups[array_rand($groups)];
                $class = $classes[array_rand($classes)];

                while ($groupClassCount[$group][$class] >= 8 || array_sum($groupClassCount[$group]) >= 27) {
                    $group = $groups[array_rand($groups)];
                    $class = $classes[array_rand($classes)];
                }

                $groupClassCount[$group][$class]++;

                $students->push([
                    'first_name' => 'Vorname' . $i,
                    'last_name' => 'Nachname' . $i,
                    'group' => $group,
                    'class' => $class,
                ]);
            }

            return $students;
        });


        return view('anwesenheit.index', [
            'children' => $students,
            'groups' => $groups,
            'classes' => $classes,
        ]);
    }
}
