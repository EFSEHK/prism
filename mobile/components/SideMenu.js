import { View, Text, Pressable, StyleSheet, Modal } from 'react-native'
import Svg, { Path } from 'react-native-svg'

export function HamburgerIcon({ color = '#0f172a' }) {
  return (
    <View style={styles.hamburger}>
      <View style={[styles.bar, { backgroundColor: color }]} />
      <View style={[styles.bar, { backgroundColor: color }]} />
      <View style={[styles.bar, { backgroundColor: color }]} />
    </View>
  )
}

export function BellIcon({ size = 18, color = '#0f172a' }) {
  return (
    <Svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
      accessibilityElementsHidden
      importantForAccessibility="no-hide-descendants"
    >
      <Path d="M15 17h5l-1.4-1.4a2 2 0 0 1-.6-1.4V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5" />
      <Path d="M9 17a3 3 0 0 0 6 0" />
    </Svg>
  )
}

export function HomeIcon({ size = 22, color = '#0f172a' }) {
  return (
    <Svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke={color}
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
      accessibilityElementsHidden
      importantForAccessibility="no-hide-descendants"
    >
      <Path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V9.5z" />
    </Svg>
  )
}

export default function SideMenu({ visible, onClose, onChangePassword, onLogout }) {
  function handleChangePassword() {
    onClose()
    onChangePassword?.()
  }

  function handleLogout() {
    onClose()
    onLogout?.()
  }

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.overlay}>
        <Pressable style={styles.backdrop} onPress={onClose} accessibilityLabel="Close menu" />
        <View style={styles.drawer}>
          <Text style={styles.drawerTitle}>Menu</Text>
          {onChangePassword ? (
            <Pressable onPress={handleChangePassword} style={styles.item}>
              <Text style={styles.label}>Change password</Text>
            </Pressable>
          ) : null}
          {onLogout ? (
            <>
              {onChangePassword ? <View style={styles.menuSeparator} /> : null}
              <Pressable onPress={handleLogout} style={styles.item}>
                <Text style={[styles.label, styles.logoutLabel]}>Logout</Text>
              </Pressable>
            </>
          ) : null}
        </View>
      </View>
    </Modal>
  )
}

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    flexDirection: 'row',
  },
  backdrop: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.45)',
  },
  drawer: {
    width: 260,
    backgroundColor: '#fff',
    paddingTop: 48,
    paddingBottom: 16,
    borderLeftWidth: 1,
    borderLeftColor: '#e2e8f0',
  },
  drawerTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: '#64748b',
    textTransform: 'uppercase',
    letterSpacing: 0.8,
    paddingHorizontal: 20,
    marginBottom: 12,
  },
  menuSeparator: {
    borderTopWidth: 1,
    borderTopColor: '#e2e8f0',
    marginTop: 8,
    marginBottom: 8,
    marginHorizontal: 20,
  },
  item: {
    paddingHorizontal: 20,
    paddingVertical: 14,
    borderRightWidth: 3,
    borderRightColor: 'transparent',
  },
  label: {
    fontSize: 16,
    fontWeight: '600',
    color: '#334155',
  },
  logoutLabel: {
    color: '#b91c1c',
  },
  hamburger: {
    width: 22,
    height: 16,
    justifyContent: 'space-between',
  },
  bar: {
    height: 2,
    borderRadius: 1,
    backgroundColor: '#0f172a',
  },
})
