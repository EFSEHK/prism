export function childName(student) {
  return [student?.first_name, student?.last_name].filter(Boolean).join(' ')
}

export function formatDate(value) {
  if (!value) return ''
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return String(value)
  return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
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
