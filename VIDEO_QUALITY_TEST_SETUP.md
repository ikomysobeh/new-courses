# Video Quality Test Setup

How to manually add quality variants for a video so the frontend quality selector can be tested.

---

## What We Need

The frontend quality selector reads from the `video_qualities` table.
Each row = one quality variant (720p, 480p, 360p) for a video.
The stream URL the frontend uses points to a real `.mp4` file on local disk.

---

## Step 1 — Find Your Video ID

Run this in MySQL (phpMyAdmin or terminal):

```sql
SELECT id, name, file_path, transcode_status FROM videos ORDER BY id DESC LIMIT 10;
```

Pick the video you want to test. Note its `id` and its `file_path`.

Example result:
```
id=13, file_path=videos/13/original.mp4
```

---

## Step 2 — Copy the Original File as Quality Variants

The stream controller reads files from the **local disk** (`storage/app/`).
For testing we just copy the original file into quality folders.

Run these commands from the project root (adjust `13` to your video id).

**Important:** Laravel's `local` disk roots at `storage/app/private/` — files must go there.

On Windows, create the folders in Explorer or run:
```
storage\app\private\videos\13\720p\
storage\app\private\videos\13\480p\
storage\app\private\videos\13\360p\
```

Then copy your quality files into those folders, e.g.:
```
storage\app\private\videos\13\720p\720p.mp4
storage\app\private\videos\13\480p\480p.mp4
storage\app\private\videos\13\360p\360p.mp4
```

---

## Step 3 — Insert Rows Into `video_qualities`

Run this SQL (replace `13` with your video id):

```sql
INSERT INTO video_qualities (video_id, quality, file_path, file_size, created_at, updated_at)
VALUES
  (13, '720p',  'videos/13/720p/720p.mp4',  500000000, NOW(), NOW()),
  (13, '480p',  'videos/13/480p/480p.mp4',  200000000, NOW(), NOW()),
  (13, '360p',  'videos/13/360p/360p.mp4',  100000000, NOW(), NOW());
```

- `file_path` must match exactly what you put in `storage/app/`
- `file_size` is in bytes — the values above are fake (500MB / 200MB / 100MB), that's fine for testing
- If rows already exist for this video, delete them first:
  ```sql
  DELETE FROM video_qualities WHERE video_id = 13;
  ```

---

## Step 4 — Update the Video Status

The frontend may hide the quality selector if `transcode_status` is not `completed`.
Set it:

```sql
UPDATE videos SET transcode_status = 'completed' WHERE id = 13;
```

---

## Step 5 — Test the API Response

Open the content viewer for that video and check the API response.
The `qualities` array should now appear:

```json
"qualities": [
  {
    "id": 1,
    "quality": "360p",
    "file_size": 100000000,
    "stream_url": "http://127.0.0.1:8000/api/media/video-quality/1?expires=...&signature=..."
  },
  {
    "id": 2,
    "quality": "480p",
    "file_size": 200000000,
    "stream_url": "..."
  },
  {
    "id": 3,
    "quality": "720p",
    "file_size": 500000000,
    "stream_url": "..."
  }
]
```

---

## Step 6 — Verify Streaming Works

Copy one of the `stream_url` values from the response and open it directly in the browser.
It should start downloading / playing the video.

If it returns 404 → the `file_path` in the database doesn't match the actual file location in `storage/app/`.

---

## Quick Summary Table

| What | Where |
|---|---|
| Files go | `storage/app/private/videos/{id}/{quality}/{quality}.mp4` |
| DB table | `video_qualities` |
| Required columns | `video_id`, `quality`, `file_path`, `file_size` |
| Video status | `videos.transcode_status = 'completed'` |
| Stream route | `GET /api/media/video-quality/{quality_id}` (signed) |

---

## Already Have a Seeder?

There is a `VideoQualitySeeder` in `database/seeders/VideoQualitySeeder.php`.
It loops all videos and inserts fake rows — but the `file_path` values it inserts are fake paths that don't exist on disk.
Use it only if you want to test that the API returns the quality list without testing actual video playback.

```bash
php artisan db:seed --class=VideoQualitySeeder
```
