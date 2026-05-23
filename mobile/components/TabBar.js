import { View, Text, Pressable, ScrollView, StyleSheet } from 'react-native'

const TABS = [
  { id: 'home', label: 'Home' },
  { id: 'homework', label: 'HW' },
  { id: 'marks', label: 'Marks' },
  { id: 'attendance', label: 'Attend' },
  { id: 'timetable', label: 'Time' },
  { id: 'feed', label: 'Feed' },
  { id: 'fees', label: 'Fees' },
  { id: 'online', label: 'Class' },
  { id: 'leave', label: 'Leave' },
  { id: 'alerts', label: 'Alerts' },
]

export default function TabBar({ active, onChange }) {
  return (
    <View style={styles.wrap}>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.row}>
        {TABS.map((t) => (
          <Pressable
            key={t.id}
            onPress={() => onChange(t.id)}
            style={[styles.tab, active === t.id && styles.tabActive]}
          >
            <Text style={[styles.label, active === t.id && styles.labelActive]}>{t.label}</Text>
          </Pressable>
        ))}
      </ScrollView>
    </View>
  )
}

const styles = StyleSheet.create({
  wrap: {
    borderTopWidth: 1,
    borderTopColor: '#e2e8f0',
    backgroundColor: '#fff',
  },
  row: { paddingHorizontal: 8, paddingVertical: 8, gap: 6 },
  tab: {
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 20,
    backgroundColor: '#f1f5f9',
  },
  tabActive: { backgroundColor: '#2563eb' },
  label: { fontSize: 13, fontWeight: '600', color: '#475569' },
  labelActive: { color: '#fff' },
})
