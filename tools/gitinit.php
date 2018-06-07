<?php

function git_init($path = './') {
    if (chdir($path)) {
        system("git init");
    }
}
