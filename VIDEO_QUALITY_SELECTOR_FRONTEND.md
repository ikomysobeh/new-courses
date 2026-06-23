# Video Quality Selector — Frontend Implementation Guide

## Why It Is Not Showing Now

The backend already returns the `qualities` array correctly:

```json
"qualities": [
  { "id": 30, "quality": "360p", "file_size": 100000000, "stream_url": "..." },
  { "id": 29, "quality": "480p", "file_size": 200000000, "stream_url": "..." },
  { "id": 28, "quality": "720p", "file_size": 500000000, "stream_url": "..." }
]
```

But the frontend has **three problems**:

1. `ContentViewerData` type does not have a `qualities` field → TypeScript ignores it
2. `VideoPlayer` component has no `qualities` prop → nowhere to pass them
3. There is no quality selector UI in the player controls

---

## Files to Change

| File | What to do |
|---|---|
| `src/pages/user/online-courses/types/user-online-courses.types.ts` | Add `VideoQuality` interface and `qualities` field to `ContentViewerData` |
| `src/pages/user/online-courses/components/video-player.tsx` | Add `qualities` prop, quality state, selector button in controls |
| `src/pages/user/online-courses/online-content-viewer-page.tsx` | Pass `qualities={data.qualities}` to `<VideoPlayer>` |

---

## Step 1 — Add Types

**File:** `src/pages/user/online-courses/types/user-online-courses.types.ts`

Add this new interface (anywhere before `ContentViewerData`):

```ts
export interface VideoQuality {
  id: number
  quality: string       // "360p" | "480p" | "720p"
  file_size: number     // bytes
  stream_url: string    // signed URL
}
```

Add `qualities` field to `ContentViewerData`:

```ts
export interface ContentViewerData {
  content_id: number
  content_type: ContentType
  title: string
  duration_seconds: number
  media_url: string
  subtitle_url: string | null
  qualities: VideoQuality[]        // ← ADD THIS LINE
  pdf_total_pages: number | null
  attachment_path: string | null
  attachment_name: string | null
  progress: ContentProgress
  next_content: ContentNavRef | null
  prev_content: ContentNavRef | null
}
```

---

## Step 2 — Update VideoPlayer

**File:** `src/pages/user/online-courses/components/video-player.tsx`

### 2a — Add import

Add `MonitorIcon` (or `TvIcon`) to the lucide imports for the quality button icon:

```ts
import {
  // ...existing icons...
  MonitorIcon,
} from "lucide-react"
```

### 2b — Add `VideoQuality` import

At the top of the file, import the type:

```ts
import type { VideoQuality } from "../types/user-online-courses.types"
```

### 2c — Add `qualities` to Props interface

```ts
interface Props {
  src: string
  subtitleUrl?: string | null
  qualities?: VideoQuality[]        // ← ADD
  resumePosition: number
  onPlay: () => void
  onPause: () => void
  onSeek: (from: number, to: number) => void
  onSpeedChange: () => void
  onFullscreen: () => void
  onTimeUpdate: (position: number, duration: number) => void
  onEnded: () => void
}
```

### 2d — Add quality state inside the component

After the existing `useState` declarations, add:

```ts
const [activeSrc, setActiveSrc] = useState(src)
const [showQualityMenu, setShowQualityMenu] = useState(false)
const [activeQuality, setActiveQuality] = useState<string>("Auto")
```

And add an effect so if the `src` prop changes (new content), the active src resets:

```ts
useEffect(() => {
  setActiveSrc(src)
  setActiveQuality("Auto")
}, [src])
```

### 2e — Use `activeSrc` on the `<video>` element

Change:
```tsx
<video
  ref={videoRef}
  src={src}          // ← CHANGE THIS
  ...
```
To:
```tsx
<video
  ref={videoRef}
  src={activeSrc}    // ← TO THIS
  ...
```

### 2f — Add `switchQuality` function

Add this function inside the component (near the other action functions like `cycleSpeed`):

```ts
function switchQuality(q: VideoQuality) {
  const el = videoRef.current
  const pos = el ? el.currentTime : 0
  const wasPlaying = el ? !el.paused : false

  setActiveSrc(q.stream_url)
  setActiveQuality(q.quality)
  setShowQualityMenu(false)

  // After the new src loads, seek back to where we were
  requestAnimationFrame(() => {
    const vid = videoRef.current
    if (!vid) return
    vid.currentTime = pos
    if (wasPlaying) void vid.play()
  })
}
```

### 2g — Add quality selector button to the controls toolbar

Inside the controls `<div className="ml-auto flex items-center gap-1">` section
(right before the fullscreen button), add:

```tsx
{qualities && qualities.length > 0 && (
  <div className="relative">
    <Button
      variant="ghost" size="sm"
      onClick={() => setShowQualityMenu(v => !v)}
      className="h-8 gap-1 rounded-full px-2 text-xs font-semibold text-white/70 hover:bg-white/10 hover:text-white"
      title="Video quality"
    >
      <MonitorIcon className="size-3.5" />{activeQuality}
    </Button>

    {showQualityMenu && (
      <div className="absolute bottom-10 right-0 z-50 min-w-[90px] overflow-hidden rounded-xl border border-white/10 bg-[#18181f] shadow-xl">
        {qualities
          .slice()
          .sort((a, b) => parseInt(b.quality) - parseInt(a.quality))
          .map(q => (
            <button
              key={q.id}
              type="button"
              onClick={() => switchQuality(q)}
              className={`flex w-full items-center justify-between gap-3 px-3 py-2 text-xs transition hover:bg-white/8 ${
                activeQuality === q.quality ? "text-indigo-400" : "text-white/70"
              }`}
            >
              <span>{q.quality}</span>
              <span className="text-white/30">{Math.round(q.file_size / 1_000_000)}MB</span>
            </button>
          ))}
      </div>
    )}
  </div>
)}
```

Also add a click-outside handler to close the menu when clicking elsewhere.
Add this `useEffect` inside the component:

```ts
useEffect(() => {
  if (!showQualityMenu) return
  function close(e: MouseEvent) {
    const target = e.target as HTMLElement
    if (!target.closest("[data-quality-menu]")) setShowQualityMenu(false)
  }
  document.addEventListener("mousedown", close)
  return () => document.removeEventListener("mousedown", close)
}, [showQualityMenu])
```

And add `data-quality-menu` attribute to the wrapper div:

```tsx
<div className="relative" data-quality-menu>
```

---

## Step 3 — Pass qualities from the page

**File:** `src/pages/user/online-courses/online-content-viewer-page.tsx`

Find where `<VideoPlayer>` is rendered (around line 242) and add the `qualities` prop:

```tsx
<VideoPlayer
  src={data.media_url}
  subtitleUrl={data.subtitle_url}
  qualities={data.qualities}          // ← ADD THIS LINE
  resumePosition={data.progress?.playback_position ?? 0}
  onPlay={session.handlePlay}
  onPause={session.handlePause}
  onSeek={session.handleSeek}
  onSpeedChange={session.handleSpeedChange}
  onFullscreen={session.handleFullscreen}
  onTimeUpdate={session.handleTimeUpdate}
  onEnded={session.handleEnded}
/>
```

---

## How It Will Work After the Change

1. Page loads → VideoPlayer plays `media_url` (original, labelled "Auto")
2. User clicks the quality button (shows "Auto" by default, next to speed button)
3. A dropdown appears listing 720p / 480p / 360p with file sizes
4. User picks a quality → player saves current position, switches to the quality's `stream_url`, seeks back to same position, resumes playing
5. Quality label in the button updates to the selected quality

---

## Notes for Backend

- No backend changes needed
- `qualities` is already in the API response
- Each `stream_url` is a signed URL valid for 4 hours — same pattern as `media_url`
- Stream endpoint: `GET /api/media/video-quality/{quality_id}` (signed, no auth token needed)
