export function formatError(e) {
  const data = e.response?.data
  if (data?.errors) return Object.values(data.errors).flat().join('\n')
  if (data?.message) return data.message
  if (e.response?.status === 403) {
    return 'Access denied for this account.'
  }
  if (e.code === 'ECONNABORTED') {
    return 'Request timed out. Check Laragon and network.'
  }
  if (e.message === 'Network Error' || e.code === 'ERR_NETWORK') {
    return 'Cannot reach the API. Check mobile/.env and Wi‑Fi.'
  }
  return e.message || 'Request failed'
}

export function formatTime(value) {
  if (!value) return ''
  const s = String(value)
  return s.length >= 5 ? s.slice(0, 5) : s
}

export function formatDate(value) {
  if (!value) return ''
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return String(value)
  return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
}

export function childName(student) {
  return [student?.first_name, student?.last_name].filter(Boolean).join(' ')
}

/** Laravel paginated JSON body or plain array. */
export function paginatedItems(body) {
  if (Array.isArray(body)) return body
  return body?.data ?? []
}
