Di Filament, kamu **bisa kontrol menu, tombol, dan akses path** berdasarkan role dengan sangat fleksibel. Ini panduan lengkap tapi ringkas:

---

## ✅ 1. **Sembunyikan Menu Navigasi Berdasarkan Role**

Di setiap `Resource` (misalnya `ProductResource`, `OrderResource`, dll), override:

### **`shouldRegisterNavigation()`**

```php
public static function shouldRegisterNavigation(): bool
{
    return auth()->user()?->role === 'superAdmin';
}
```

Untuk resource yang bisa diakses semua role:

```php
public static function shouldRegisterNavigation(): bool
{
    return in_array(auth()->user()?->role, ['superAdmin', 'admin', 'counter']);
}
```

---

## ✅ 2. **Batasi Routing via Browser (Langsung Akses URL)**

Override metode `canViewAny`, `canCreate`, dll:

```php
public static function canViewAny(): bool
{
    return in_array(auth()->user()?->role, ['admin', 'superAdmin']);
}

public static function canCreate(): bool
{
    return auth()->user()?->role === 'superAdmin';
}
```

Dengan ini, user dengan role tidak sesuai akan **diblokir meskipun akses langsung via URL**.

---

## ✅ 3. **Tampilkan Tombol Tertentu Berdasarkan Role**

Di halaman Order (misalnya `OrderResource/Pages/ViewOrder.php`), override `getHeaderActions()` atau `getActions()`:

```php
use Filament\Actions\Action;

protected function getHeaderActions(): array
{
    $actions = [];

    if (auth()->user()->role === 'superAdmin') {
        $actions[] = Action::make('superButton')
            ->label('Only Super Admin')
            ->action(fn () => $this->someLogic());
    }

    if (auth()->user()->role === 'admin') {
        $actions[] = Action::make('adminButton')
            ->label('Only Admin')
            ->action(fn () => $this->someOtherLogic());
    }

    return $actions;
}
```

---

## ✅ 4. **Tips Tambahan**

* Tambahkan helper di `User` model untuk rapi:

```php
public function hasRole($roles): bool
{
    return in_array($this->role, (array) $roles);
}
```

* Lalu bisa tulis begini:

```php
if (auth()->user()->hasRole(['superAdmin', 'admin'])) {
    // tampilkan tombol atau menu
}
```

---

### 🚀 Kesimpulan:

| Fitur                   | Cara                                                 |
| ----------------------- | ---------------------------------------------------- |
| Sembunyikan menu        | `shouldRegisterNavigation()`                         |
| Batasi akses URL        | `canViewAny()`, `canCreate()`, dll                   |
| Tampilkan tombol khusus | `getActions()` / `getHeaderActions()` + kondisi role |
| Kontrol granular        | Gunakan helper di model User                         |

Kalau kamu mau, aku bisa bantu buatkan contoh `ProductResource`, `OrderResource` dan file halaman yang sesuai role.
