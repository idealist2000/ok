# Falcı Profilleri — Kurulum ve Kullanım Kılavuzu

## Gereksinimler

- WordPress 5.8 veya üzeri
- PHP 7.4 veya üzeri
- (İsteğe bağlı) OpenAI veya Anthropic API anahtarı

---

## Kurulum

1. `falci-profilleri` klasörünü `/wp-content/plugins/` dizinine yükleyin.
2. WordPress Yönetici → **Eklentiler** sayfasından **Falcı Profilleri** eklentisini etkinleştirin.
3. Etkinleştirme sırasında özel yazı tipi otomatik kaydedilir ve kalıcı bağlantılar güncellenir.

---

## Ayarlar (Admin Panel)

**Falcılar → Ayarlar** menüsünden aşağıdaki ayarları yapılandırın:

| Ayar | Açıklama |
|------|----------|
| **YZ Sağlayıcısı** | `OpenAI (GPT-3.5)` veya `Anthropic (Claude Haiku)` |
| **API Anahtarı** | İlgili sağlayıcının API anahtarı |
| **Otomatik YZ Onayı** | Açık: Başvurular YZ tarafından otomatik değerlendirilir. Kapalı: Manuel onay gerekir. |
| **Bildirim E-postası** | Yeni başvurularda bildirim gönderilecek adres |

> API anahtarı boş bırakılırsa YZ denetimi yapılmaz; başvurular "Beklemede" durumuna düşer.

---

## Shortcode Kullanımı

### Başvuru Formu
```
[falci_basvuru_formu]
```
Herhangi bir sayfaya eklenebilir. Ziyaretçiler bu form aracılığıyla falcı başvurusu yapabilir.

### Falcı Dizini (Listeleme)
```
[falci_listesi]
[falci_listesi uzmanlik="tarot" limit="9" columns="3"]
```

| Parametre | Varsayılan | Açıklama |
|-----------|-----------|----------|
| `uzmanlik` | (boş) | Filtreleme için uzmanlık anahtarı (ör. `tarot`, `kahve_fali`) |
| `limit` | `12` | Sayfa başına gösterilecek falcı sayısı |
| `columns` | `3` | Izgara sütun sayısı (1–4) |

**Geçerli uzmanlık anahtarları:**
`kahve_fali`, `tarot`, `el_fali`, `yildizname`, `katina`, `ruya_tabiri`, `numeroloji`, `kristal_kure`, `astroloji`, `geomancy`

### Tekil Profil Gömme
```
[falci_profil id="123"]
```
Belirli bir falcı profilini sayfaya gömer. `id` değeri Falcı yazı tipinin post ID'sidir.

---

## Profil Sayfaları

- **Arşiv (dizin):** `yoursite.com/falcilar/`
- **Tekil profil:** `yoursite.com/falci/[rumuz]/`

Eklenti, tek falcı sayfası için kendi şablonunu (`templates/single-falci.php`) kullanır.  
Aktif temanızın `single-falci.php` dosyası varsa tema şablonu öncelik kazanır.

---

## Admin Paneli

**Falcılar** menüsü altında:

- **Tüm Falcılar:** Tüm başvuruları, durumlarını ve hızlı işlem butonlarını listeler.
  - **Durum Filtresi:** Listenin üstündeki açılır menüden duruma göre filtre uygulayabilirsiniz.
  - **Hızlı İşlem:** "Onayla" ve "Reddet" butonlarıyla tek tıkla durum değiştirin.
- **Falcı Düzenleme:** Her falcı için tüm alanları (ad, biyografi, fotoğraf vb.) düzenleyin.
- **Durum Kutusu:** Sağ kenar çubuğunda durum değiştirme ve YZ ret gerekçesini görüntüleme.

### Durum Akışı

```
Başvuru gönderildi
       │
       ▼
  [Beklemede]  ←──── YZ denetimi başarısız / API anahtarı yok
       │
       ├─── YZ onaylarsa ──► [Yayında]   (profil herkese görünür)
       │
       └─── YZ reddederse ─► [Reddedildi] (ret gerekçesi kaydedilir)
       
Admin her zaman durumu manuel olarak değiştirebilir.
```

---

## İletişim Butonları

Tüm profil sayfalarında ve listelemelerde iletişim butonları **sabit** şu numaraya yönlendirilmektedir:

> **+90 539 357 34 07**

- **Ara:** `tel:+905393573407`
- **WhatsApp:** `https://wa.me/905393573407?text=Merhaba, [falcı rumuzu] hakkında bilgi almak istiyorum.`

Falcılar kendi iletişim bilgilerini ekleyemez; biyografi ve tanıtım metinlerinde telefon/link tespit edilirse başvuru otomatik reddedilir.

Telefon numarasını değiştirmek için `falci-profilleri.php` dosyasındaki sabitleri güncelleyin:

```php
define( 'FALCI_PHONE', '+905393573407' );
define( 'FALCI_PHONE_DISPLAY', '+90 539 357 34 07' );
define( 'FALCI_WHATSAPP_BASE', 'https://wa.me/905393573407' );
```

---

## Sıkça Sorulan Sorular

**S: Profil fotoğrafı neden yüklenmiyor?**  
Sunucunun `wp-content/uploads/` dizinine yazma izni olduğundan ve PHP `file_uploads = On` ayarının açık olduğundan emin olun.

**S: YZ onayı çalışmıyor.**  
Ayarlar sayfasından doğru sağlayıcıyı seçtiğinizi ve API anahtarının geçerli olduğunu kontrol edin. "Otomatik YZ Onayı" kutucuğunun işaretli olması gerekir.

**S: Kalıcı bağlantılar (permalink) 404 veriyor.**  
WordPress Yönetici → Ayarlar → Kalıcı Bağlantılar sayfasını açıp "Değişiklikleri Kaydet" butonuna tıklayın.

**S: Tasarım temamla çakışıyor.**  
Plugin CSS'i `falci-` önekli sınıflar kullanmaktadır. Temanızda override yapabilmek için `assets/css/frontend.css` dosyasını düzenleyin veya temanızın `style.css` dosyasına ek stiller ekleyin.
