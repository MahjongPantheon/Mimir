<?php
// @codingStandardsIgnoreFile
use Phinx\Migration\AbstractMigration;

class UpdateVersion1m1 extends AbstractMigration
{
    public function up()
    {
        $table = $this->table('event');
        $table
            ->addColumn('sync_start', 'integer')
            ->addColumn('auto_seating', 'integer')
            ->addColumn('sort_by_games', 'integer')
            ->addColumn('use_timer', 'integer')
            ->addColumn('allow_player_append', 'integer')
            ->addColumn('is_online', 'integer')
            ->addColumn('is_textlog', 'integer')
            ->update();

        $this->query("
            UPDATE event SET
                sync_start = 1,
                auto_seating = 1,
                sort_by_games = 1,
                allow_player_append = 0,
                is_online = 0,
                use_timer = 1,
                is_textlog = 0
                WHERE type = 'offline_interactive_tournament';
        ");

        $this->query("
            UPDATE event SET
                sync_start = 0,
                auto_seating = 0,
                sort_by_games = 0,
                allow_player_append = 1,
                is_online = 0,
                use_timer = 0,
                is_textlog = 0
                WHERE type = 'offline';
        ");

        $this->query("
            UPDATE event SET
                sync_start = 1,
                auto_seating = 1,
                sort_by_games = 1,
                allow_player_append = 0,
                is_online = 1,
                use_timer = 0,
                is_textlog = 0
                WHERE type = 'online';
        ");
    }
}
