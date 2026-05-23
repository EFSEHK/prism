import { View, Text, StyleSheet, Pressable } from 'react-native'

export function Section({ title, children, badge }) {
  return (
    <View style={ui.section}>
      <View style={ui.sectionHeader}>
        <Text style={ui.sectionTitle}>{title}</Text>
        {badge != null ? (
          <View style={ui.badge}>
            <Text style={ui.badgeText}>{badge}</Text>
          </View>
        ) : null}
      </View>
      {children}
    </View>
  )
}

export function Card({ title, meta, body, sub, onPress }) {
  const content = (
    <View style={ui.card}>
      {title ? <Text style={ui.cardTitle}>{title}</Text> : null}
      {meta ? <Text style={ui.cardMeta}>{meta}</Text> : null}
      {body ? <Text style={ui.cardBody}>{body}</Text> : null}
      {sub ? <Text style={ui.cardSub}>{sub}</Text> : null}
    </View>
  )
  if (onPress) {
    return (
      <Pressable onPress={onPress} style={({ pressed }) => pressed && { opacity: 0.9 }}>
        {content}
      </Pressable>
    )
  }
  return content
}

export function EmptyNote({ text }) {
  return <Text style={ui.empty}>{text}</Text>
}

export const ui = StyleSheet.create({
  section: { marginBottom: 18 },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 8,
  },
  sectionTitle: { fontSize: 17, fontWeight: '700', color: '#0f172a' },
  badge: {
    backgroundColor: '#e2e8f0',
    borderRadius: 12,
    paddingHorizontal: 8,
    paddingVertical: 2,
  },
  badgeText: { fontSize: 12, fontWeight: '600', color: '#475569' },
  card: {
    backgroundColor: '#fff',
    borderRadius: 10,
    padding: 14,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: '#e2e8f0',
  },
  cardTitle: { fontSize: 16, fontWeight: '600', color: '#0f172a' },
  cardMeta: { fontSize: 13, color: '#64748b', marginTop: 4 },
  cardBody: { fontSize: 14, color: '#334155', marginTop: 6, lineHeight: 20 },
  cardSub: { fontSize: 12, color: '#94a3b8', marginTop: 6 },
  empty: { fontSize: 14, color: '#94a3b8', fontStyle: 'italic', paddingVertical: 4 },
  err: { color: '#b91c1c', marginBottom: 12, lineHeight: 20 },
  hero: {
    backgroundColor: '#1e40af',
    borderRadius: 12,
    padding: 18,
    marginBottom: 16,
  },
  heroGreeting: { color: '#bfdbfe', fontSize: 13 },
  heroName: { color: '#fff', fontSize: 22, fontWeight: '700', marginTop: 4 },
  heroMeta: { color: '#dbeafe', fontSize: 13, marginTop: 8 },
})
