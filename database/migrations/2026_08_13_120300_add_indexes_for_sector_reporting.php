<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The dashboard runs several counts per page load and every one of them is
     * now additionally filtered by sector. These tables carried no indexes at
     * all beyond their primary keys.
     *
     * @var array<string, array<string, array<int, string>>>
     */
    private array $indexes = [
        'orders' => [
            'orders_status_renew_date_index' => ['status', 'renew_date'],
            'orders_user_id_index' => ['user_id'],
            'orders_assigned_user_id_index' => ['assigned_user_id'],
        ],
        'userprofiles' => [
            'userprofiles_user_id_index' => ['user_id'],
            'userprofiles_sector_id_index' => ['sector_id'],
        ],
        'payment_history' => [
            'payment_history_order_id_index' => ['order_id'],
            'payment_history_payment_date_time_index' => ['payment_date_time'],
        ],
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->indexes as $table_name => $indexes) {
            Schema::table($table_name, function (Blueprint $table) use ($table_name, $indexes) {
                foreach ($indexes as $index_name => $columns) {
                    if (! $this->indexExists($table_name, $index_name)) {
                        $table->index($columns, $index_name);
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach ($this->indexes as $table_name => $indexes) {
            Schema::table($table_name, function (Blueprint $table) use ($table_name, $indexes) {
                foreach (array_keys($indexes) as $index_name) {
                    if ($this->indexExists($table_name, $index_name)) {
                        $table->dropIndex($index_name);
                    }
                }
            });
        }
    }

    private function indexExists(string $table_name, string $index_name): bool
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() !== 'mysql') {
            return false;
        }

        return $connection->table('information_schema.statistics')
            ->where('table_schema', $connection->getDatabaseName())
            ->where('table_name', $connection->getTablePrefix().$table_name)
            ->where('index_name', $index_name)
            ->exists();
    }
};
