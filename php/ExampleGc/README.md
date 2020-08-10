# ExampleGc

this is only example code for studying Garbage Collection on PHP.
so, this contain No Good Code.

so, please only refer for understanding GC mechanism.

## Related Article
work in progress. i will modify it when Article is prepared.

you can check below site. (Japanese)

- https://genie-oh.github.io/
- https://qiita.com/genie-oh

## how to run

`Laravel Framework 7.13.0` is used for making this example.
so, if you try to run it, Laravel is required.

check below step.

- install Laravel Framework.
- copy `*.php` to `LaravelApplicationPath/app/Console/Commands/`.
- check command is exists of `example:gc`&`example:weak` on your `artisan` command.
- if you can find it, run `artisan example:gc` or `artisan example:weak`.
    - if you can't find it, please check `App/Console/Kernel.php`.