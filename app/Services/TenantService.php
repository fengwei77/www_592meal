<?php

namespace App\Services;

use App\Models\Tenant;
use Database\Seeders\DefaultCategoriesSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Tenant Service (租戶 Schema 管理服務)
 *
 * 負責建立、管理和切換 PostgreSQL Schema
 *
 * PostgreSQL Schema 機制說明：
 * - Schema 是資料庫內的命名空間，類似檔案系統的資料夾
 * - 透過 search_path 控制查詢時的 Schema 搜尋順序
 * - 當執行 "SELECT * FROM categories" 時，PostgreSQL 會在 search_path 的第一個 schema 中尋找該資料表
 * - 例如：SET search_path TO tenant_1 會讓所有查詢導向 tenant_1.categories
 *
 * 多租戶架構：
 * - public schema：存放共用資料 (users, stores, tenants)
 * - tenant_{id} schemas：存放各店家獨立資料 (categories, products, orders)
 * - 透過動態切換 search_path 實現資料隔離
 */
class TenantService
{
    /**
     * 建立租戶 Schema 並執行初始化
     *
     * 此方法執行完整的租戶初始化流程：
     * 1. 建立獨立的 PostgreSQL Schema
     * 2. 授予必要的存取權限
     * 3. 在新 Schema 中建立資料表結構 (Migration)
     * 4. 填充初始資料 (Seeder)
     * 5. 確保最終回到 public schema，避免污染後續操作
     *
     * 失敗時會自動清理：
     * - 刪除已建立的 Schema (CASCADE 會同時刪除其中所有物件)
     * - 恢復 search_path 到 public
     * - 記錄錯誤日誌供追蹤
     *
     * @param Tenant $tenant 租戶 Model (必須包含 schema_name)
     * @return void
     * @throws \Exception 當 Schema 建立、Migration 或 Seeding 失敗時
     */
    public function createTenantSchema(Tenant $tenant): void
    {
        $schemaName = $tenant->schema_name;

        try {
            // 1. 建立 PostgreSQL Schema
            // IF NOT EXISTS 確保冪等性 (Idempotency)，重複執行不會報錯
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$schemaName}");
            Log::info("Created schema: {$schemaName}");

            // 2. 設定 Schema 權限 (從 Story 1.1 學到的教訓)
            // PostgreSQL 預設不會將 Schema 權限授予建立者以外的用戶
            // 必須明確 GRANT 才能讓應用程式用戶存取 Schema 內的物件
            $dbUser = config('database.connections.pgsql.username');
            DB::statement("GRANT ALL ON SCHEMA {$schemaName} TO {$dbUser}");

            // 3. 切換到新 Schema
            // search_path 是 session-level 設定，只影響當前連線
            // 後續的 Migration 和 Seeder 會在此 Schema 下執行
            // 注意：search_path 會持續到手動切換或連線關閉
            DB::statement("SET search_path TO {$schemaName}");

            // 4. 執行 Tenant Migrations
            // --path 指定執行 database/migrations/tenant 下的檔案
            // 這些 Migration 只包含租戶專屬的資料表 (categories, products 等)
            // --force 跳過確認提示 (適用於自動化流程)
            Artisan::call('migrate', [
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
            Log::info("Migrated tenant schema: {$schemaName}");

            // 5. 執行初始資料 Seeder (建立預設分類)
            // DefaultCategoriesSeeder 會在當前 search_path 的 Schema 下插入資料
            // 因為已切換到 tenant schema，所以資料會寫入 tenant_{id}.categories
            $seeder = new DefaultCategoriesSeeder();
            $seeder->run();
            Log::info("Seeded default categories for: {$schemaName}");

            // 6. 切換回 Public Schema
            // 重要：必須恢復到 public schema，避免後續查詢錯誤導向 tenant schema
            // 例如：若不切換回 public，後續的 users 查詢會在 tenant schema 中找不到資料表
            DB::statement("SET search_path TO public");

        } catch (\Exception $e) {
            // === 錯誤處理與清理機制 ===

            // 1. 確保 search_path 恢復到 public
            // 即使發生錯誤，也必須恢復到安全狀態
            DB::statement("SET search_path TO public");

            // 2. 刪除可能已建立的 Schema
            // CASCADE 會遞迴刪除 Schema 下的所有物件 (tables, indexes, sequences 等)
            // 確保不留下不完整的 Schema 結構
            DB::statement("DROP SCHEMA IF EXISTS {$schemaName} CASCADE");

            // 3. 記錄詳細錯誤資訊供除錯
            Log::error("Failed to create tenant schema: {$schemaName}", [
                'error' => $e->getMessage(),
                'tenant_id' => $tenant->id,
            ]);

            // 4. 重新拋出例外，讓呼叫者 (StoreOnboarding) 可以處理回滾
            throw $e;
        }
    }

    /**
     * 切換到指定 Schema
     *
     * 動態切換 PostgreSQL search_path，讓後續的資料庫操作在指定 Schema 下執行。
     *
     * 使用情境：
     * - 在 Middleware 中根據 subdomain 切換到對應租戶的 Schema
     * - 在 Queue Job 中處理特定店家的資料時切換 Schema
     * - 在測試中手動切換 Schema 驗證資料隔離
     *
     * 注意事項：
     * - search_path 是 session-level 設定，只影響當前資料庫連線
     * - 使用連線池時，必須在每次請求開始時重新設定 search_path
     * - 應始終在操作完成後切換回 public schema，避免影響其他請求
     *
     * @param string $schemaName Schema 名稱 (例如: "tenant_1", "public")
     * @return void
     */
    public function switchSchema(string $schemaName): void
    {
        DB::statement("SET search_path TO {$schemaName}");
    }

    /**
     * 取得當前 Schema
     *
     * 查詢當前連線的 search_path 設定，回傳第一個 Schema 名稱。
     *
     * 使用情境：
     * - 測試驗證 Schema 切換是否成功
     * - 除錯時確認當前操作的 Schema
     * - 記錄日誌時追蹤 Schema 上下文
     *
     * 注意：
     * - search_path 可包含多個 Schema (例如: "tenant_1, public")
     * - current_schema() 函式只回傳第一個存在的 Schema
     * - 若 search_path 中的 Schema 不存在，會回傳下一個存在的 Schema
     *
     * @return string 當前 Schema 名稱 (例如: "tenant_1" 或 "public")
     */
    public function getCurrentSchema(): string
    {
        $result = DB::select("SELECT current_schema()");
        return $result[0]->current_schema;
    }
}
