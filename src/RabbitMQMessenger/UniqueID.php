<?php


namespace KignOrg\RabbitMQMessenger;


class UniqueID
{
    private static UniqueID $instance;
    private array $ids;

    private function __construct()
    {
        $this->ids = [];
    }

    public static function getInstance(): static
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function get(string $prefix = ""): string
    {
        $id = $this->generateId($prefix);
        while ($this->exists($id)) {
            $id = $this->generateId($prefix);
        }

        $this->ids[$id] = $id;
        return $id;
    }

    public function exists(string $id): bool
    {
        return in_array($id, $this->ids);
    }

    public function release(string $id): static
    {
        unset($this->ids[$id]);
        return $this;
    }

    public function releaseAll(): static
    {
        $this->ids = [];
        return $this;
    }

    private function generateId(string $prefix = ""): string
    {
        return uniqid($prefix, true);
    }
}
