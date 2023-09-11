./vendor/bin/phpcs --report-file=lint-report.txt & phpcs_pid=$!

while ps -p $phpcs_pid > /dev/null; do
    sleep 1
done

cat lint-report.txt

rm -f lint-report.txt

