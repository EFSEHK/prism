function normalizeLocalPart(value) {
  let v = String(value || '').trim().toLowerCase()
  if (v.includes('@')) {
    v = v.split('@', 2)[0]
  }
  return v.replace(/[\s-]/g, '')
}

function usernameCandidates(rawIdentifier, email) {
  const candidates = []
  const local = normalizeLocalPart(rawIdentifier)
  if (local) candidates.push(local)
  if (email) {
    const emailLocal = normalizeLocalPart(email)
    if (emailLocal && !candidates.includes(emailLocal)) candidates.push(emailLocal)
  }
  return candidates
}

function containsConsecutiveUsernameChars(password, usernames, length = 3) {
  const pw = password.toLowerCase()
  for (const username of usernames) {
    const user = username.toLowerCase()
    if (user.length < length) continue
    for (let i = 0; i <= user.length - length; i++) {
      const substr = user.slice(i, i + length)
      if (substr && pw.includes(substr)) return true
    }
  }
  return false
}

export function validatePassword(password, rawIdentifier, email = null) {
  if (!password || password.length < 8) {
    return 'Password must be at least 8 characters.'
  }
  if (!/[a-z]/.test(password) || !/[A-Z]/.test(password) || !/[0-9]/.test(password) || !/[\W_]/.test(password)) {
    return 'Password must include upper and lower case letters, a number, and a special character.'
  }
  const usernames = usernameCandidates(rawIdentifier, email)
  if (containsConsecutiveUsernameChars(password, usernames)) {
    return 'Password must not contain 3 consecutive characters from your username.'
  }
  return null
}
