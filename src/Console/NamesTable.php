<?php

namespace Ajthinking\Tinx\Console;

class NamesTable
{
    /**
     * @param \Ajthinking\Tinx\Console\TinxCommand $command
     * @return void
     * */
    public static function for(TinxCommand $command)
    {
        return new static($command);
    }

    /**
     * @param \Ajthinking\Tinx\Console\TinxCommand $command
     * @return void
     * */
    private function __construct(TinxCommand $command)
    {
        $this->command = $command;
        $this->names = $this->command->getNames();
    }

    /**
     * @return void
     * */
    public function conditionallyRender()
    {
        if ($this->shouldRender()) {
            $this->render();
        }
    }

    /**
     * @return bool
     * */
    private function shouldRender()
    {
        $totalNames = count($this->names);

        if ($this->command->option('verbose')) {
            return true;
        }

        if (0 === $totalNames) {
            return false;
        }

        $namesTableLimit = (int) config('tinx.names_table_limit', 10);

        if ($namesTableLimit === -1) {
            return true;
        }

        if ($totalNames <= $namesTableLimit) {
            return true;
        }

        return false;
    }

    /**
     * @param array $filters
     * @return void
     * */
    public function render(...$filters)
    {
        if (0 === count($this->names)) {
            return $this->command->warn("No models found (see: config/tinx.php > namespaces_and_paths).");
        }

        $rows = $this->getRows();

        if ($filters) {
            $rows = $this->filterRows($rows, $filters);
            if (0 === count($rows)) {
                return $this->command->warn($this->getFiltersWarning($filters));
            }
        }

        $this->command->table($this->getHeaders(), $rows->toArray());
    }

        /**
     * @return array
     * */
    private function getHeaders()
    {
        return ['Class', 'Shortcuts'];
    }

    /**
     * @return \Illuminate\Support\Collection
     * */
    private function getRows()
    {
        return collect($this->names)->map(function ($name, $class) {
            return [$class, "\${$name}, \${$name}_, {$name}()"];
        });
    }

    /**
     * @param \Illuminate\Support\Collection $rows
     * @param array $filters
     * @return \Illuminate\Support\Collection
     * */
    private function filterRows($rows, $filters = [])
    {
        $regex = '/'.implode('|', $filters).'/i';

        return $rows->filter(function ($row) use ($regex) {
            return preg_match($regex, $row[0]);
        });
    }

    /**
     * @param array $filters
     * @return void
     * */
    private function getFiltersWarning($filters)
    {
        return sprintf(
            'No classes found for %s [%s].',
            str_plural('filter', count($filters)),
            implode(', ', $filters)
        );
    }
}
