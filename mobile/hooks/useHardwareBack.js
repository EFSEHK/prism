import { useEffect } from 'react'
import { BackHandler } from 'react-native'

/**
 * Intercept the Android hardware / gesture back action.
 * Handler should return true when the event is consumed.
 */
export function useHardwareBack(handler, deps = []) {
  useEffect(() => {
    const sub = BackHandler.addEventListener('hardwareBackPress', () => handler() === true)
    return () => sub.remove()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, deps)
}
