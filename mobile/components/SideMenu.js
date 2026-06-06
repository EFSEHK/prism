import { View, Text, Pressable, ScrollView, StyleSheet, Modal } from 'react-native'

export const NAV_ITEMS_HOME = [{ id: 'home', label: 'Home' }]

export const NAV_ITEMS_CHILD = [
  { id: 'dashboard', label: 'Dashboard' },
  { id: 'homework', label: 'Homework' },
  { id: 'marks', label: 'Marks' },
  { id: 'attendance', label: 'Attendance' },
  { id: 'timetable', label: 'Timetable' },
  { id: 'notifications', label: 'Notifications' },
  { id: 'fees', label: 'Fees' },
  { id: 'online', label: 'Online Class' },
  { id: 'leave', label: 'Leave' },
  { id: 'alerts', label: 'Alerts' },
  { id: 'home', label: 'Switch child' },
]

/** @deprecated Use NAV_ITEMS_HOME or NAV_ITEMS_CHILD */
export const NAV_ITEMS = NAV_ITEMS_CHILD

export function navItemsForContext(hasSelectedChild) {
  return hasSelectedChild ? NAV_ITEMS_CHILD : NAV_ITEMS_HOME
}

export function HamburgerIcon() {
  return (
    <View style={styles.hamburger}>
      <View style={styles.bar} />
      <View style={styles.bar} />
      <View style={styles.bar} />
    </View>
  )
}

export default function SideMenu({ visible, active, items, onChange, onClose }) {
  const navItems = items ?? NAV_ITEMS_CHILD
  const hasMultipleItems = navItems.length > 1

  function select(id) {
    onChange(id)
    onClose()
  }

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.overlay}>
        <View style={styles.drawer}>
          <Text style={styles.drawerTitle}>Menu</Text>
          <ScrollView showsVerticalScrollIndicator={false}>
            {navItems.map((item) => (
              <View key={item.id}>
                {hasMultipleItems && item.id === 'home' ? <View style={styles.menuSeparator} /> : null}
                <Pressable
                  onPress={() => select(item.id)}
                  style={[styles.item, active === item.id && styles.itemActive]}
                >
                  <Text style={[styles.label, active === item.id && styles.labelActive]}>
                    {item.label}
                  </Text>
                </Pressable>
              </View>
            ))}
          </ScrollView>
        </View>
        <Pressable style={styles.backdrop} onPress={onClose} accessibilityLabel="Close menu" />
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
    borderRightWidth: 1,
    borderRightColor: '#e2e8f0',
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
    borderLeftWidth: 3,
    borderLeftColor: 'transparent',
  },
  itemActive: {
    backgroundColor: '#eff6ff',
    borderLeftColor: '#2563eb',
  },
  label: {
    fontSize: 16,
    fontWeight: '600',
    color: '#334155',
  },
  labelActive: {
    color: '#2563eb',
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
