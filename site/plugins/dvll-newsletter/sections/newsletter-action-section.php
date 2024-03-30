<?php

return [
    'props' => [
        'status' => function () {
            return $this->model()->status();
        },
        'id' => function () {
            return $this->model()->id();
        },
        // 'rendered' => function () {
        //     return $this->model()->render();
        // },
    ]
];
