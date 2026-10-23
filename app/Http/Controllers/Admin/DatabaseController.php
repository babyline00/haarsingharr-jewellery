<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DatabaseController extends Controller
{
    public function info(): View
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        $tables = [];
        $totalRows = 0;

        try {
            $tableNames = DB::select('SHOW TABLES');
            $key = 'Tables_in_' . $config['database'];
            foreach ($tableNames as $table) {
                $name = $table->{$key} ?? array_values((array) $table)[0];
                $count = DB::table($name)->count();
                $tables[] = ['name' => $name, 'rows' => $count];
                $totalRows += $count;
            }
        } catch (\Throwable $e) {
            $tables = [];
        }

        return view('admin.database.info', [
            'connection' => $connection,
            'host' => $config['host'] ?? 'N/A',
            'port' => $config['port'] ?? 'N/A',
            'database' => $config['database'] ?? 'N/A',
            'username' => $config['username'] ?? 'N/A',
            'tables' => $tables,
            'totalRows' => $totalRows,
            'totalTables' => count($tables),
        ]);
    }
}
