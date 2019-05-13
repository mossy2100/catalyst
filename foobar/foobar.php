<?php

/**
 * @file
 * Count from 1 to 100, specifying foo, bar, or foobar, when divisible by 3, 5,
 * or both, respectively.
 *
 * @author Shaun Moss <shaun@astromultimedia.com>
 */

for ($i = 1; $i <= 100; $i++) {
  if ($i % 15 === 0) {
    echo 'foobar';
  }
  elseif ($i % 3 === 0) {
    echo 'foo';
  }
  elseif ($i % 5 === 0) {
    echo 'bar';
  }
  else {
    echo $i;
  }
  echo $i < 100 ? ', ' : "\n";
}
