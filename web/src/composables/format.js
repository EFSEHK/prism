export const APP_TIMEZONE = 'Asia/Karachi'

const DATE_ONLY = /^(\d{4})-(\d{2})-(\d{2})$/
const DATETIME_NAIVE = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})(?::\d{2})?$/

const MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

function displayDate(day, month, year) {
  const mmm = MONTHS_SHORT[parseInt(month, 10) - 1] ?? month
  return `${day} ${mmm} ${year}`
}

function isIsoDateTime(value) {
  const s = String(value)
  return s.includes('T') || s.endsWith('Z') || /[+-]\d{2}:\d{2}$/.test(s)
}

function parseNaive(value) {
  const s = String(value).trim()
  if (isIsoDateTime(s)) return null
  let m = s.match(DATE_ONLY)
  if (m) {
    return { day: m[3], month: m[2], year: m[1] }
  }
  m = s.match(DATETIME_NAIVE)
  if (m) {
    return {
      day: m[3],
      month: m[2],
      year: m[1],
      hour: m[4],
      minute: m[5],
    }
  }
  return null
}

function karachiPartsFromInstant(value) {
  const d = value instanceof Date ? value : new Date(value)
  if (Number.isNaN(d.getTime())) return null
  const parts = new Intl.DateTimeFormat('en-GB', {
    timeZone: APP_TIMEZONE,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  }).formatToParts(d)
  return {
    day: parts.find((p) => p.type === 'day')?.value,
    month: parts.find((p) => p.type === 'month')?.value,
    year: parts.find((p) => p.type === 'year')?.value,
    hour: parts.find((p) => p.type === 'hour')?.value,
    minute: parts.find((p) => p.type === 'minute')?.value,
  }
}

function formatInstantDate(value) {
  const d = value instanceof Date ? value : new Date(value)
  if (Number.isNaN(d.getTime())) return ''
  return new Intl.DateTimeFormat('en-GB', {
    timeZone: APP_TIMEZONE,
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(d)
}

/** Display a calendar date as dd mmm yyyy (Asia/Karachi). */
export function formatDate(value) {
  if (!value) return ''
  const naive = parseNaive(value)
  if (naive) return displayDate(naive.day, naive.month, naive.year)
  return formatInstantDate(value) || String(value)
}

/** Display a timestamp as dd mmm yyyy HH:mm (Asia/Karachi). */
export function formatDateTime(value) {
  if (!value) return ''
  const naive = parseNaive(value)
  if (naive) {
    const date = displayDate(naive.day, naive.month, naive.year)
    if (naive.hour != null && naive.minute != null) {
      return `${date} ${naive.hour}:${naive.minute}`
    }
    return date
  }
  const d = value instanceof Date ? value : new Date(value)
  if (Number.isNaN(d.getTime())) return String(value)
  const date = formatInstantDate(d)
  const time = new Intl.DateTimeFormat('en-GB', {
    timeZone: APP_TIMEZONE,
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  }).format(d)
  return `${date} ${time}`
}

/** Value for HTML date inputs (yyyy-mm-dd) in Asia/Karachi. */
export function dateToInputValue(value) {
  if (!value) return ''
  const naive = parseNaive(value)
  if (naive) return `${naive.year}-${naive.month}-${naive.day}`
  const parts = karachiPartsFromInstant(value)
  if (!parts?.day) return ''
  return `${parts.year}-${parts.month}-${parts.day}`
}

/** Today's date as yyyy-mm-dd in Asia/Karachi. */
export function todayInputDate() {
  return dateToInputValue(new Date())
}

/** Current month as yyyy-mm in Asia/Karachi. */
export function currentMonthInput() {
  const parts = karachiPartsFromInstant(new Date())
  if (!parts?.month) return ''
  return `${parts.year}-${parts.month}`
}

export function formatPeriod(start, end) {
  return `${formatDate(start)} – ${formatDate(end)}`
}

export function childName(student) {
  return [student?.first_name, student?.last_name].filter(Boolean).join(' ')
}

export function formatTime(value) {
  if (!value) return ''
  const s = String(value)
  return s.length >= 5 ? s.slice(0, 5) : s
}

export function paginated(body) {
  if (Array.isArray(body)) return body
  return body?.data ?? []
}
