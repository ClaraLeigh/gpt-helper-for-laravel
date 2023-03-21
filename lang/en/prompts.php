<?php

return [
    'start' => 'I want you to act as a Laravel developer, keep in mind best practices from alexeymezenin/laravel-best-practices and make changes to this file. ',
    'content' => 'This is the existing code: ```:contents```. ',
    'refinement' => 'Do not write explanations. Do not write comments. Keep it as readable as possible. Only output the new file content and nothing more. ',
    'changes' => 'I wish you to make the following changes: ":prompt". ',
    'end' => 'The new file is: ',
    'types' => [
        'model' => 'This is a laravel model file, you need to remember any $fillable, $hidden, $casts vars. You will also need to include any relevant relationships, including any additional ones you can think of.',
        'controller' => 'This is a laravel controller file. Remember to expand on all existing functions and add any additional ones required.',
        'request' => 'This is a laravel request file. Remember to include all the rules for each possible request parameter.',
        'migration' => 'This is a laravel migration file. Remember to include all the columns and their types.',
        'factory' => 'This is a laravel factory file. Remember to include all the fields that this model will use and link any relationships.',
        'seeder' => 'This is a laravel seeder file. Remember to seed the basic data needed.',
        'test' => 'This is a laravel test file. Write all the tests you think will likely be needed.',
        'laravel' => 'This is a laravel file. Remember to include all the relevant code that you normally see in these types of laravel files.',
    ]
];
