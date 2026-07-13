import AsyncStorage from '@react-native-async-storage/async-storage'

const TOKEN_KEY = 'efsc_mobile_token'
const USER_KEY = 'efsc_mobile_user'

export async function loadSession() {
  try {
    const [token, rawUser] = await Promise.all([
      AsyncStorage.getItem(TOKEN_KEY),
      AsyncStorage.getItem(USER_KEY),
    ])
    if (!token) return null
    let user = null
    if (rawUser) {
      try {
        user = JSON.parse(rawUser)
      } catch {
        user = null
      }
    }
    return { token, user }
  } catch {
    return null
  }
}

export async function saveSession(token, user) {
  try {
    const ops = [AsyncStorage.setItem(TOKEN_KEY, token || '')]
    if (user) {
      ops.push(AsyncStorage.setItem(USER_KEY, JSON.stringify(user)))
    } else {
      ops.push(AsyncStorage.removeItem(USER_KEY))
    }
    await Promise.all(ops)
  } catch {
    /* ignore persistence failures */
  }
}

export async function clearSession() {
  try {
    await Promise.all([
      AsyncStorage.removeItem(TOKEN_KEY),
      AsyncStorage.removeItem(USER_KEY),
    ])
  } catch {
    /* ignore */
  }
}
